<?php
session_start(); // THIS MUST BE THE VERY FIRST LINE
require_once 'db.php';
require_once 'mail_helper.php'; // Include your mail helper
 require_once 'csrf_helper.php'; 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }
    $con = get_db_connection(); // Get connection if needed for email/phone existence checks
    $errors = [];

    // All existing validations remain unchanged...
    $username = trim($_POST['username']);
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z ]+$/', $username)) {
        $errors[] = "Username is required and must contain only letters (min 3 chars).";
    }

    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        // Check if email already registered in DB
        $stmt = mysqli_prepare($con, "SELECT id FROM info WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    $rawPass = trim($_POST['password']);
    $pass = null; // Initialize $pass
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_@!])[A-Za-z\d_@!]{8,}$/', $rawPass)) {
        $errors[] = "Password must be 8+ chars, include letter, number, and _@!.";
    } else {
        $pass = password_hash($rawPass, PASSWORD_DEFAULT);
    }

    $url = trim($_POST['url']);
    $secure_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    if (!empty($url)) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Valid URL is required.";
        } else {
            $domain = parse_url($url, PHP_URL_HOST);
            $allowed = ['youtube.com', 'google.com', 'github.com', 'linkedin.com', 'yourdomain.com'];
            $clean_domain = str_replace('www.', '', strtolower($domain));
            if (!in_array($clean_domain, $allowed)) {
                $errors[] = "URL must be from allowed secure domains.";
            }
        }
    }

    $tel = htmlspecialchars(trim($_POST['telephone']), ENT_QUOTES, 'UTF-8');
    if (!preg_match('/^[0-9]{7,11}$/', $tel)) {
        $errors[] = "Valid telephone number is required.";
    } else {
        // Check if phone number already used in DB
        $stmt = mysqli_prepare($con, "SELECT id FROM info WHERE tel = ?");
        mysqli_stmt_bind_param($stmt, "s", $tel);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Phone number already used.";
        }
        mysqli_stmt_close($stmt);
    }

    $dob = htmlspecialchars($_POST['dob'], ENT_QUOTES, 'UTF-8');
    if (empty($dob)) {
        $errors[] = "Date of birth is required.";
    }

    $volume = $_POST['volume'];
    if (!is_numeric($volume)) {
        $errors[] = "Volume must be numeric.";
    }

    $age = htmlspecialchars($_POST['age'], ENT_QUOTES, 'UTF-8');
    if (!is_numeric($age) || $age < 0) {
        $errors[] = "Valid positive age required.";
    }

    $gender = $_POST['gender'] ?? '';
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    $skills = ''; // Initialize skills
    if (!isset($_POST['skills']) || !is_array($_POST['skills'])) {
        $errors[] = "At least one skill must be selected.";
    } else {
        $skills = implode(',', $_POST['skills']);
    }

    $dept = $_POST['department'];
    if (empty($dept)) {
        $errors[] = "Department is required.";
    }

    $color = $_POST['color'] ?? '';
    $feedback = htmlspecialchars(trim($_POST['feedback']), ENT_QUOTES, 'UTF-8');

    // === FILE UPLOAD SECTION ===
    $temp_dir = 'D:/xampp/temp_uploads/'; // Temporary directory for files
    // Ensure temp upload directory exists
    if (!is_dir($temp_dir)) {
        if (!mkdir($temp_dir, 0755, true)) {
            $errors[] = "Failed to create temporary upload directory at " . $temp_dir . ". Check folder permissions.";
        }
    }

    $pic = null;
    $file = null;

    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Profile picture is required.";
    } else {
        $pic_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($pic_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, WEBP images allowed for profile picture.";
        }
    }

    if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "PDF file is required.";
    } else {
        $file_type = mime_content_type($_FILES['file_upload']['tmp_name']);
        if ($file_type !== 'application/pdf') {
            $errors[] = "Only PDF file allowed.";
        }
    }

    // If there are validation errors, redirect back
    if (!empty($errors)) {
        mysqli_close($con); // Close DB connection
        $msg = urlencode($errors[0]);
        header("Location: user_register.php?error=$msg");
        exit;
    }

    // Move uploaded files to temporary directory after all validations pass
    $pic_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
    $file_ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);

    $temp_pic_name = uniqid('pic_', true) . '.' . $pic_ext;
    $temp_file_name = uniqid('file_', true) . '.' . $file_ext;

    if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $temp_dir . $temp_pic_name)) {
        $errors[] = "Failed to move profile picture to temp directory.";
    }
    if (!move_uploaded_file($_FILES['file_upload']['tmp_name'], $temp_dir . $temp_file_name)) {
        $errors[] = "Failed to move PDF file to temp directory.";
    }

    // If there are errors after moving files, redirect
    if (!empty($errors)) {
        // Clean up any files that might have been moved before an error occurred
        if ($temp_pic_name && file_exists($temp_dir . $temp_pic_name))
            unlink($temp_dir . $temp_pic_name);
        if ($temp_file_name && file_exists($temp_dir . $temp_file_name))
            unlink($temp_dir . $temp_file_name);
        mysqli_close($con); // Close DB connection
        $msg = urlencode($errors[0]);
        header("Location: user_register.php?error=$msg");
        exit;
    }

    // Generate OTP and expiry
    $otp = rand(100000, 999999);
    $otp_expiry = date("Y-m-d H:i:s", strtotime('+5 minutes')); // OTP valid for 5 minutes

    // Store all user data in session for verification page
    $_SESSION['pending_user'] = [
        'username' => $username,
        'email' => $email,
        'password' => $pass, // Hashed password
        'url' => $secure_url,
        'tel' => $tel,
        'dob' => $dob,
        'volume' => $volume,
        'age' => $age,
        'gender' => $gender,
        'skills' => $skills,
        'department' => $dept,
        'color' => $color,
        'feedback' => $feedback,
        'profile_picture' => $temp_pic_name, // Store temp file name
        'file_upload' => $temp_file_name,   // Store temp file name
        'otp_code' => $otp,
        'otp_expiration' => $otp_expiry
    ];

    mysqli_close($con); // Close DB connection as it's no longer needed in this script

    // Send OTP email using the helper function
    // Sanitize email and username to prevent injection
    $safe_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $safe_username = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $username);

    // Send OTP email using the helper function
    $result = send_otp_email($safe_email, $safe_username, $otp);
    if ($result === true) {
        header("Location: verify_otp.php?email=" . urlencode($safe_email));
        exit;
    } else {
        // Clean up temp files
        if (isset($temp_pic_name) && file_exists($temp_dir . $temp_pic_name))
            unlink($temp_dir . $temp_pic_name);
        if (isset($temp_file_name) && file_exists($temp_dir . $temp_file_name))
            unlink($temp_dir . $temp_file_name);

        unset($_SESSION['pending_user']); //  RECOMMENDED
        $msg = urlencode("Failed to send OTP email. Please try again. Error: " . $result);
        header("Location: user_register.php?error=$msg");
        exit;
    }


} else {
    // If not a POST request, redirect to registration page
    header("Location: user_register.php");
    exit;
}
?>