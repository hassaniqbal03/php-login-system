<?php
session_start();
require_once 'auth_helper.php'; // JWT helper
require_once 'db.php';         // Database connection function

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in();
if (!$admin_data) {
    header("Location: user_login.php?error=unauthorized_access");
    exit;
}

$conn = get_db_connection();
$errors = [];
$secure_upload_dir = "D:/xampp/secure_uploads/"; // Make sure this directory exists and is writable

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $old_profile_picture = $_POST['old_profile_picture'] ?? '';
    $old_file_upload = $_POST['old_file_upload'] ?? '';

    if ($user_id === 0) {
        $errors[] = "Invalid user ID provided.";
    }

    // --- Data Collection and Validation ---
    $username = trim($_POST['username'] ?? '');
     $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z ]+$/', $username)) {
        $errors[] = "Username is required, must be at least 3 characters, and can only contain letters (a-z, A-Z).";
    }
   

    $email = trim($_POST['email'] ?? '');
     $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        // Check if the new email already exists for another user
        $check_email_sql = "SELECT id FROM info WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_email_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors[] = "Email already registered by another user.";
        }
        $check_stmt->close();
    }
   

    $role = $_POST['role'] ?? 'user';
     $role = htmlspecialchars($role, ENT_QUOTES, 'UTF-8');
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = "Invalid role selected.";
    }
   

    $url = trim($_POST['url'] ?? '');
     $secure_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $allowed_domains = [
        'https://youtube.com',
        'https://google.com',
        'https://github.com',
        'https://linkedin.com',
        'https://yourdomain.com'
    ];
    if (!empty($url)) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid URL format.";
        } else {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            $host = parse_url($url, PHP_URL_HOST);
            $clean_host = str_replace('www.', '', strtolower($host));
            $full_domain = strtolower($scheme . '://' . $clean_host);
            if (!in_array($full_domain, $allowed_domains)) {
                $errors[] = "Only trusted domains allowed.";
            }
        }
    }
    $tel = trim($_POST['tel'] ?? '');
    $tel = htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
    if (!empty($tel) && !preg_match('/^\+?[0-9]{7,15}$/', $tel)) {
        $errors[] = "Invalid phone number format. Must be 7-15 digits, optional '+' at start.";
    }

    $dob = trim($_POST['dob'] ?? ''); // YYYY-MM-DD
     $dob = htmlspecialchars($dob, ENT_QUOTES, 'UTF-8');
    $age = (int)($_POST['age'] ?? 0);
    if (!empty($dob) && !empty($age)) {
        // Basic DOB-age consistency check (optional, can be more robust)
        $birth_date = new DateTime($dob);
        $today = new DateTime();
        $calculated_age = $today->diff($birth_date)->y;
        if ($calculated_age !== $age) {
            // $errors[] = "Age does not match date of birth."; // Consider if this is a strict requirement
        }
    }
   

    $volume = (int)($_POST['volume'] ?? 0);
    if ($volume < 1 || $volume > 100) {
        $errors[] = "Volume must be between 1 and 100.";
    }

    $skills = trim($_POST['skills'] ?? '');
    $skills = htmlspecialchars($skills, ENT_QUOTES, 'UTF-8');

    $department = trim($_POST['department'] ?? '');
    $department = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');

    $color = $_POST['color'] ?? '';
    $color = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');

    $feedback = trim($_POST['feedback'] ?? '');
    $feedback = htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8');

    // --- File Upload Handling ---
    $profile_picture_filename = $old_profile_picture; // Default to old file
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $pic_tmp_name = $_FILES['profile_picture']['tmp_name'];
        $pic_name = $_FILES['profile_picture']['name'];
        $pic_mime_type = mime_content_type($pic_tmp_name);

        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($pic_mime_type, $allowed_image_types)) {
            $extension = pathinfo($pic_name, PATHINFO_EXTENSION);
            $unique_pic_name = uniqid('profile_') . '.' . $extension;
            $target_pic_path = $secure_upload_dir . $unique_pic_name;

            if (move_uploaded_file($pic_tmp_name, $target_pic_path)) {
                // Delete old profile picture if a new one is uploaded
                if (!empty($old_profile_picture) && file_exists($secure_upload_dir . $old_profile_picture)) {
                    unlink($secure_upload_dir . $old_profile_picture);
                }
                $profile_picture_filename = $unique_pic_name;
            } else {
                $errors[] = "Failed to upload new profile picture.";
            }
        } else {
            $errors[] = "Invalid profile picture format. Only JPG, PNG, GIF are allowed.";
        }
    }

    $file_upload_filename = $old_file_upload; // Default to old file
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $pdf_tmp_name = $_FILES['file_upload']['tmp_name'];
        $pdf_name = $_FILES['file_upload']['name'];
        $pdf_mime_type = mime_content_type($pdf_tmp_name);

        if ($pdf_mime_type === 'application/pdf') {
            $extension = pathinfo($pdf_name, PATHINFO_EXTENSION);
            $unique_pdf_name = uniqid('doc_') . '.' . $extension;
            $target_pdf_path = $secure_upload_dir . $unique_pdf_name;

            if (move_uploaded_file($pdf_tmp_name, $target_pdf_path)) {
                // Delete old PDF file if a new one is uploaded
                if (!empty($old_file_upload) && file_exists($secure_upload_dir . $old_file_upload)) {
                    unlink($secure_upload_dir . $old_file_upload);
                }
                $file_upload_filename = $unique_pdf_name;
            } else {
                $errors[] = "Failed to upload new PDF file.";
            }
        } else {
            $errors[] = "Invalid file format. Only PDF files are allowed for CV.";
        }
    }

    // If there are validation errors, redirect back with error message
    if (!empty($errors)) {
        $error_message = urlencode(implode("<br>", $errors));
        header("Location: admin_edit_user.php?id=" . $user_id . "&error=" . $error_message);
        exit;
    }

    // --- Update Database ---
    $sql = "UPDATE info SET 
            username=?, email=?, url=?, tel=?, dob=?, volume=?, age=?, skills=?, department=?, profile_picture=?, file_upload=?, color=?, feedback=?, role=?
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    // s: username, s: email, s: url, s: tel, s: dob, i: volume, i: age,  s: skills, s: department, s: profile_picture, s: file_upload, s: color, s: feedback, s: role, i: id
    $stmt->bind_param("sssssiisssssssi",
        $username, $email, $url, $tel, $dob, $volume, $age,  $skills, $department,
        $profile_picture_filename, $file_upload_filename, $color, $feedback, $role, $user_id
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: all_users.php?id=" . $user_id . "&updated=1");
        exit;
    } else {
        $stmt->close();
        $conn->close();
        $errors[] = "Database update failed: " . $stmt->error;
        $error_message = urlencode(implode("<br>", $errors));
        header("Location: admin_edit_user.php?id=" . $user_id . "&error=" . $error_message);
        exit;
    }

} else {
    // Not a POST request, redirect to all users page
    header("Location: all_users.php");
    exit;
}
?>