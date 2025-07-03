<?php
session_start();
require_once 'auth_helper.php';
require_once 'db.php';

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in();
if (!$admin_data) {
    header("Location: user_login.php?error=unauthorized_access");
    exit;
}

$conn = get_db_connection();

// --- Search Integration (similar to all_users.php) ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_clause = '';
$search_params = [];
$param_types = '';

if (!empty($search_query)) {
    $search_clause = " WHERE username LIKE ? OR email LIKE ? OR skills LIKE ? OR department LIKE ?";
    $search_term_like = '%' . $search_query . '%';
    $search_params = [$search_term_like, $search_term_like, $search_term_like, $search_term_like];
    $param_types = 'ssss';
}

// Fetch all relevant user data (applying search filter if present)
// IMPORTANT: Do NOT export 'password' field directly!
$sql = "SELECT id, username, email, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback, role 
        FROM info" . $search_clause . "
        ORDER BY id DESC"; // No LIMIT/OFFSET needed for export

$stmt = $conn->prepare($sql);

if (!empty($search_query)) {
    $stmt->bind_param($param_types, ...$search_params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// --- Generate CSV ---

$filename = "users_export_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w'); // Open output stream

// CSV Headers
// Define which columns you want in the CSV and their display names
$headers = [
    'ID', 'Username', 'Email', 'Website', 'Phone', 'Date of Birth', 'Volume', 'Age',
    'Gender', 'Skills', 'Department', 'Profile Picture Filename', 'Uploaded File Filename',
    'Favorite Color', 'Feedback', 'Role'
];
fputcsv($output, $headers); // Write headers to CSV

// CSV Data
foreach ($users as $row) {
    // Reorder and select data based on headers.
    // Ensure the order matches $headers array.
    $csv_row = [
        $row['id'],
        $row['username'],
        $row['email'],
        $row['url'],
        $row['tel'],
        $row['dob'],
        $row['volume'],
        $row['age'],
        $row['gender'],
        $row['skills'],
        $row['department'],
        $row['profile_picture'], // Filename only, not the file itself
        $row['file_upload'],     // Filename only
        $row['color'],
        $row['feedback'],
        $row['role']
    ];
    fputcsv($output, $csv_row); // Write data row to CSV
}

fclose($output); // Close the output stream
exit;

?>