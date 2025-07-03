
<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
       require_once 'db.php';
    $con = get_db_connection();
    // Collect and validate form inputs
    $errors = [];

    $username = trim($_POST['username']);
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

if (empty($username) || strlen($username) < 3 || !preg_match('/^[a-zA-Z ]+$/', $username)) {
    $errors[] = "Username is required, must be at least 3 characters, and can only contain letters (a-z, A-Z)."; 
}

      // 2. Email Validation and Uniqueness Check
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    } else {
        // Check if email already exists in the database
        $check_email = "SELECT id FROM info WHERE email = ?";
        $check_stmt = mysqli_prepare($con, $check_email);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt); // Store the result so you can check row count

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "This email is already registered. Please use a different one.";
        }
        mysqli_stmt_close($check_stmt); // Close this statement
    }
 // 5. Telephone // regex pattern
    $tel = trim($_POST['telephone']);
    $tel = htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
    if (empty($tel) || !preg_match('/^[0-9]{7,11}$/', $tel)) {
        $errors[] = "Valid telephone number is required.";
    }else{
         $check_tel = "SELECT id FROM info WHERE tel = ?";
        $check_stmt = mysqli_prepare($con, $check_tel);
        mysqli_stmt_bind_param($check_stmt, "s", $tel);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt); // Store the result so you can check row count

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "This phone no is already registered. Please use a different one.";
        }
        mysqli_stmt_close($check_stmt); // Close this statement
    }
    
    // 3. Password - required, min 6 chars
  $Pass = trim($_POST['password']);
  $Pass = htmlspecialchars($Pass, ENT_QUOTES, 'UTF-8');
if (empty($Pass) || !preg_match('/^[a-zA-Z0-9_@!]{8}$/', $Pass)) {
    $errors[] = "Password must be at least 8 characters and can contain letters(a-z ,A-Z), numbers, and _ @ ! only.";
} else {
    $pass = password_hash($Pass, PASSWORD_DEFAULT); // Encrypt for security
}

    // 4. URL 

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
        $errors[] = "URL must be valid.";

    } else {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host   = parse_url($url, PHP_URL_HOST); 
        $clean_host = str_replace('www.', '', strtolower($host)); 
        $full_domain = strtolower($scheme . '://' . $clean_host);

        // Step 4: Check against allowed list
        if (!in_array($full_domain, $allowed_domains)) {
            $errors[] = "Only secure trusted domains (https) are allowed.";
        }
    }
}

    // 6. DOB 
    $dob = $_POST['dob'];
       $dob = htmlspecialchars($dob, ENT_QUOTES, 'UTF-8');
    if (empty($dob)) {
        $errors[] = "Date of birth is required.";
    }

    // 7. Volume 
    $volume = $_POST['volume'];
    if (!is_numeric($volume)) {
        $errors[] = "Volume must be a number.";
    }

    // 8. Age 
    $age = $_POST['age'];
       $age=htmlspecialchars($age,ENT_QUOTES,'UTF-8');
    if (!is_numeric($age) || $age < 0) {
        $errors[] = "Age must be a positive number.";
    }

    // 10. Gender 
    $gender = $_POST['gender'] ?? '';
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    // 11. Skills 
    if (!isset($_POST['skills']) || !is_array($_POST['skills'])) {
        $errors[] = "Please select at least one skill.";
    } else {
        $skills = implode(',', $_POST['skills']);
    }

    // 12. Department 
    $dept = $_POST['department'];
    if (empty($dept)) {
        $errors[] = "Department is required.";
    }
   // 13. Profile Picture 
if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] != UPLOAD_ERR_OK) {
    $errors[] = "Profile picture is required.";
} else {
    $file_tmp = $_FILES['profile_picture']['tmp_name'];
    $file_type = mime_content_type($file_tmp); 
    $allowed_image_types = ['image/jpeg', 'image/png', 'image/webp'];

    if (in_array($file_type, $allowed_image_types)) {
        $unique_id = uniqid('', true);
        $pic_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_pic_name = $unique_id . "." . $pic_ext;
        $destination = "D:/xampp/secure_uploads/" . $new_pic_name;
        move_uploaded_file($file_tmp, $destination);
        $pic = $new_pic_name;
    } else {
        $errors[] = "Only JPG, PNG, or WEBP images are allowed for profile picture.";
    }
}

    // 14. File Upload 
   if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == UPLOAD_ERR_OK) {

  $file_name = $_FILES['file_upload']['name'];
$file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
$unique_file = uniqid() . "." . $file_ext;
$save_path = "D:/xampp/secure_uploads/" . $unique_file;

$file_tmp = $_FILES['file_upload']['tmp_name'];
$file_type = mime_content_type($file_tmp);

if ($file_type === 'application/pdf') {
    move_uploaded_file($file_tmp, $save_path);
    $file = $unique_file; 
} else {
    $errors[] = "Only PDF files allowed.";
    $file=null;
}


} else {
    $file = null;
}

    // 15. Color 
    $color = $_POST['color'] ?? '';

    // 16. Feedback - optional
    $feedback_raw = trim($_POST['feedback']);
     $feedback = htmlspecialchars($feedback_raw, ENT_QUOTES, 'UTF-8');
    

    //  If any validation fails, stop heres
    if (!empty($errors)) {
       
        echo "Validation Errors:<br>"; 
        echo $errors[0] . "<br>"; 
        exit; 
      
    }

    $sql = "INSERT INTO info
        (username, email, password, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($con, $sql);

    mysqli_stmt_bind_param($stmt, "sssssssiissssss",
        $username, $email, $pass, $url, $tel, $dob, $volume, $age,
        $gender, $skills, $dept, $pic, $file, $color, $feedback
    );

    if (mysqli_stmt_execute($stmt)) {
      header("Location: user_register.php?registered=1");
     exit;

       
    } else {
        echo "Error:" . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
    mysqli_close($con);
}
?>
