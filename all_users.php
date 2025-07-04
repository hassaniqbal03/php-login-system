/* This PHP code is a part of an admin panel for managing registered users. Here is a summary of what
the code does: */
<?php
session_start();
require_once 'auth_helper.php'; // JWT helper ko include karein
require_once 'db.php';         // Database connection function ko include karein

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in(); 

if (!$admin_data) {
    clear_auth_cookie(); 
    if (isset($_SESSION['user'])) {
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session
    }
   header("Location: user_login.php?expired=1&role=admin");

    exit; // Stop script execution
}
$conn = get_db_connection();

// --- Search Setup ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_clause = '';
$search_params = [];
$param_types = '';

if (!empty($search_query)) {
    // Add LIKE conditions for relevant columns
    $search_clause = " WHERE username LIKE ? OR email LIKE ? OR skills LIKE ? OR department LIKE ?";
    $search_term_like = '%' . $search_query . '%';
    $search_params = [$search_term_like, $search_term_like, $search_term_like, $search_term_like];
    $param_types = 'ssss'; // Four 's' for four string parameters
}

// --- Pagination Setup ---
$records_per_page = 10; // Har page par kitne users show karne hain
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Total users count (for pagination - now considers search)
$stmt_count = $conn->prepare("SELECT COUNT(id) AS total FROM info" . $search_clause);
if (!empty($search_query)) {
    $stmt_count->bind_param($param_types, ...$search_params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$stmt_count->close();

// Fetch users with pagination (now considers search)
$sql = "SELECT id, username, email, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback, role 
        FROM info" . $search_clause . "
        ORDER BY id DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);

// Bind parameters for search and pagination
if (!empty($search_query)) {
    $param_types .= 'ii'; // Add two 'i' for limit and offset
    $search_params[] = $offset;
    $search_params[] = $records_per_page;
    $stmt->bind_param($param_types, ...$search_params);
} else {
    // No search query, just bind limit and offset
    $stmt->bind_param("ii", $offset, $records_per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Users - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 1200px; margin: 20px auto; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions a {
            display: inline-block;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .actions .view-btn { background-color: #007bff; color: white; }
        .actions .edit-btn { background-color: #ffc107; color: #333; }
        .actions .delete-btn { background-color: #dc3545; color: white; }
        .actions a:hover { opacity: 0.9; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007bff;
            background-color: #fff;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        .pagination a:hover:not(.active) { background-color: #f2f2f2; }
        .top-links { text-align: right; margin-bottom: 20px; }
        .top-links a {
            background-color: #5cb85c;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 10px;
        }
        .top-links a.logout { background-color: #dc3545; }
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }
        .search-form input[type="text"] {
            width: 70%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 1em;
        }
        .search-form button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 1em;
        }
        .search-form button:hover {
            background-color: #0056b3;
        }
        .search-results-info {
            text-align: center;
            margin-bottom: 15px;
            font-style: italic;
            color: #666;
        }
        .top-links .download-btn {
    background-color: #28a745; /* Green color for download */
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    margin-left: 10px;
}
.top-links .download-btn:hover {
    opacity: 0.9;
}
    </style>
</head>
<body>
    <div class="container">
             <h2>All Registered Users</h2>
       
      

        <form method="GET" action="all_users.php" class="search-form">
            <input type="text" name="search" placeholder="Search by name, email, skills, or department..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($search_query)): ?>
            <p class="search-results-info">Showing results for "<?= htmlspecialchars($search_query) ?>"</p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Profile Pic</th>
                    <th>CV File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td>
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="serve_file.php?file=<?= urlencode($user['profile_picture']) ?>" alt="Pic" width="50">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($user['file_upload'])): ?>
                                    <a href="serve_file.php?file=<?= urlencode($user['file_upload']) ?>" target="_blank">View PDF</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="admin_view_user.php?id=<?= htmlspecialchars($user['id']) ?>" class="view-btn">View</a>
                                <a href="admin_edit_user.php?id=<?= htmlspecialchars($user['id']) ?>" class="edit-btn">Edit</a>
                                <a href="#" class="delete-btn" onclick="confirmDelete(<?= htmlspecialchars($user['id']) ?>, '<?= htmlspecialchars($user['username']) ?>')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
          
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php
                    $pagination_link = "all_users.php?page=" . $i;
                    if (!empty($search_query)) {
                        $pagination_link .= "&search=" . urlencode($search_query);
                    }
                    ?>
                    <a href="<?= $pagination_link ?>" class="<?= ($i === $current_page) ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
                         <div class="top-links">
            <a href="dashboard_admin.php">Admin Dashboard</a>
            <a href="export_users_to_excel.php<?= !empty($search_query) ? '?search=' . urlencode($search_query) : '' ?>" class="download-btn" style="background-color: #28a745; color: white;">Download Users (CSV)</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    <script>
        function confirmDelete(userId, username) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete user: " + username + ". This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'admin_delete_user.php?id=' + userId;
                }
            });
        }
    </script>

    </script>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'User Updated!',
            text: 'User profile has been updated successfully.',
            showConfirmButton: false,
            timer: 2500
        });
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'User Deleted!',
            text: 'User has been successfully deleted.',
            showConfirmButton: false,
            timer: 2500
        });
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && !empty($_GET['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?= htmlspecialchars($_GET['error']) ?>',
            showConfirmButton: true
        });
    </script>
    <?php endif; ?>

</body>
</html>
</body>
</html>