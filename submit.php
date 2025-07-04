<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once 'db.php';
    $con = get_db_connection();
    $errors = [];

    // 1. Username
    $username = trim($_POST['username']);
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z ]+$/', $username)) {
        $errors[] = "Username is required and must contain only letters (min 3 chars).";
    }

    // 2. Email
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        $stmt = mysqli_prepare($con, "SELECT id FROM info WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    // 3. Password
    $rawPass = trim($_POST['password']);
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_@!])[A-Za-z\d_@!]{8,}$/', $rawPass)) {
        $errors[] = "Password must be 8+ chars, include letter, number, and _@!.";
    } else {
        $pass = password_hash($rawPass, PASSWORD_DEFAULT);
    }

    // 4. URL
    $url = trim($_POST['url']);
    $secure_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    if (!empty($url)) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Valid URL is required.";
        } else {
            $domain = parse_url($url, PHP_URL_HOST);
            $allowed = ['youtube.com','google.com','github.com','linkedin.com','yourdomain.com'];
            $clean_domain = str_replace('www.', '', strtolower($domain));
            if (!in_array($clean_domain, $allowed)) {
                $errors[] = "URL must be from allowed secure domains.";
            }
        }
    }

    // 5. Telephone
    $tel = htmlspecialchars(trim($_POST['telephone']), ENT_QUOTES, 'UTF-8');
    if (!preg_match('/^[0-9]{7,11}$/', $tel)) {
        $errors[] = "Valid telephone number is required.";
    } else {
        $stmt = mysqli_prepare($con, "SELECT id FROM info WHERE tel = ?");
        mysqli_stmt_bind_param($stmt, "s", $tel);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Phone number already used.";
        }
        mysqli_stmt_close($stmt);
    }

    // 6. DOB
    $dob = htmlspecialchars($_POST['dob'], ENT_QUOTES, 'UTF-8');
    if (empty($dob)) {
        $errors[] = "Date of birth is required.";
    }

    // 7. Volume
    $volume = $_POST['volume'];
    if (!is_numeric($volume)) {
        $errors[] = "Volume must be numeric.";
    }

    // 8. Age
    $age = htmlspecialchars($_POST['age'], ENT_QUOTES, 'UTF-8');
    if (!is_numeric($age) || $age < 0) {
        $errors[] = "Valid positive age required.";
    }

    // 9. Gender
    $gender = $_POST['gender'] ?? '';
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    // 10. Skills
    if (!isset($_POST['skills']) || !is_array($_POST['skills'])) {
        $errors[] = "At least one skill must be selected.";
    } else {
        $skills = implode(',', $_POST['skills']);
    }

    // 11. Department
    $dept = $_POST['department'];
    if (empty($dept)) {
        $errors[] = "Department is required.";
    }

    // 12. Color
    $color = $_POST['color'] ?? '';

    // 13. Feedback
    $feedback = htmlspecialchars(trim($_POST['feedback']), ENT_QUOTES, 'UTF-8');

    // === FILE UPLOAD SECTION ===
    $upload_dir = 'D:/xampp/secure_uploads/';
    $pic = null;
    $file = null;

    // Validate picture
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "Profile picture is required.";
    } else {
        $pic_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($pic_type, $allowed_types)) {
            $errors[] = "Only JPG, PNG, WEBP images allowed.";
        }
    }

    // Validate file
    if (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] != UPLOAD_ERR_OK) {
        $errors[] = "PDF file is required.";
    } else {
        $file_type = mime_content_type($_FILES['file_upload']['tmp_name']);
        if ($file_type !== 'application/pdf') {
            $errors[] = "Only PDF file allowed.";
        }
    }

    // If all clear, then move files and insert
    if (empty($errors)) {
        // Picture
        $pic_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $pic = uniqid('pic_', true) . '.' . $pic_ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $pic);

        // File
        $file_ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
        $file = uniqid('file_', true) . '.' . $file_ext;
        move_uploaded_file($_FILES['file_upload']['tmp_name'], $upload_dir . $file);

        // Insert to DB
        $sql = "INSERT INTO info
        (username, email, password, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "sssssssiissssss",
            $username, $email, $pass, $secure_url, $tel, $dob, $volume, $age,
            $gender, $skills, $dept, $pic, $file, $color, $feedback
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_close($con);
            header("Location: user_register.php?registered=1");
            exit;
        } else {
            // Rollback file uploads
            if (file_exists($upload_dir . $pic)) unlink($upload_dir . $pic);
            if (file_exists($upload_dir . $file)) unlink($upload_dir . $file);
            die("DB Error: " . mysqli_stmt_error($stmt));
        }
    } else {
        // Show first error on redirect
        $msg = urlencode($errors[0]);
        header("Location: user_register.php?error=$msg");
        exit;
    }
}
?>
