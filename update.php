<?php

require_once 'db.php';
require_once 'csrf_helper.php';
$con = get_db_connection();

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
     if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Fetch old profile picture and file
    $old_sql = "SELECT profile_picture, file_upload FROM info WHERE email = ?";
    $old_stmt = mysqli_prepare($con, $old_sql);
    mysqli_stmt_bind_param($old_stmt, "s", $email);
    mysqli_stmt_execute($old_stmt);
    $old_result = mysqli_stmt_get_result($old_stmt);
    $old_row = mysqli_fetch_assoc($old_result);
    $old_picture = $old_row['profile_picture'];
    $old_file = $old_row['file_upload'];
    mysqli_stmt_close($old_stmt);

    // 1. Username
    $username = trim($_POST['username']);
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z ]+$/', $username)) {
        $errors[] = "Username must be at least 3 characters and only letters/spaces.";
    }

    // 2. URL
    $url = trim($_POST['url']);
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

    // 3. Telephone
    $tel = trim($_POST['tel']);
    $tel = htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
    if (empty($tel) || !preg_match('/^[0-9]{7,11}$/', $tel)) {
        $errors[] = "Telephone must be 7-11 digits.";
    }

    // 4. Age
    $age = $_POST['age'];
    $age = htmlspecialchars($age, ENT_QUOTES, 'UTF-8');
    if (!is_numeric($age) || $age < 0) {
        $errors[] = "Age must be a positive number.";
    }

    // 5. Skills
    $skills = trim($_POST['skills']);
    $skills = htmlspecialchars($skills, ENT_QUOTES, 'UTF-8');

    // 6. Department
    $department = trim($_POST['department']);
    $department = htmlspecialchars($department, ENT_QUOTES, 'UTF-8');
    if (empty($department)) {
        $errors[] = "Department is required.";
    }

    // 🔐 Secure Folder
    $secure_dir = "D:/xampp/secure_uploads/";

    // 7. Profile Picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
        $unique_id = uniqid('', true);
        $pic_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_pic_name = $unique_id . "." . $pic_ext;
        $profile_tmp = $_FILES['profile_picture']['tmp_name'];
        $pic_type = mime_content_type($profile_tmp);

        if (in_array($pic_type, ['image/jpeg', 'image/png'])) {
            $profile_path = $secure_dir . $new_pic_name;
            move_uploaded_file($profile_tmp, $profile_path);

            if (!empty($old_picture) && file_exists($secure_dir . $old_picture) && $old_picture !== $new_pic_name) {
                unlink($secure_dir . $old_picture);
            }

            $profile_picture = $new_pic_name;
        } else {
            $errors[] = "Only JPG and PNG files allowed for profile picture.";
            $profile_picture = $old_picture;
        }
    } else {
        $profile_picture = $old_picture;
    }

    // 8. File Upload (PDF)
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['file_upload']['tmp_name'];
        $file_type = mime_content_type($file_tmp);

        if ($file_type === 'application/pdf') {
            $file_ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid() . "." . $file_ext;
            $file_path = $secure_dir . $new_file_name;

            move_uploaded_file($file_tmp, $file_path);

            if (!empty($old_file) && file_exists($secure_dir . $old_file) && $old_file !== $new_file_name) {
                unlink($secure_dir . $old_file);
            }

            $file_upload = $new_file_name;
        } else {
            $errors[] = "Only PDF files allowed.";
            $file_upload = $old_file;
        }
    } else {
        $file_upload = $old_file;
    }

    // 9. Feedback
    $feedback = trim($_POST['feedback']);
    $feedback = htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8');

    if (!empty($errors)) {
        echo "Validation Errors:<br>";
        foreach ($errors as $err) {
            echo "- $err<br>";
        }
        exit;
    }

    // 🔄 Update Query
    $sql = "UPDATE info SET 
        username=?, url=?, tel=?, age=?, 
        skills=?, department=?, profile_picture=?, file_upload=?, feedback=?
        WHERE email=?";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ssssisssss",
        $username, $url, $tel, $age,
        $skills, $department, $profile_picture, $file_upload, $feedback, $email
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location:user_edit.php?updated=1&email=" . urlencode($email));
exit;

    } else {
        echo "Failed to update: " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>
