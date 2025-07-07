<?php
session_start();
require_once 'db.php';
require_once 'mail_helper.php';
require_once 'csrf_helper.php';
// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
$email = $_GET['email'] ?? '';
$error = '';
$success_message = '';

$pending_user = $_SESSION['pending_user'] ?? null;

// RESEND OTP
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $pending_user) {
    $safe_email = filter_var($pending_user['email'], FILTER_SANITIZE_EMAIL);
    $safe_username = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $pending_user['username']);

    $new_otp = rand(100000, 999999);
    $new_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $_SESSION['pending_user']['otp_code'] = $new_otp;
    $_SESSION['pending_user']['otp_expiration'] = $new_expiry;

    $result = send_otp_email($safe_email, $safe_username, $new_otp, true);
    if ($result === true) {
        header("Location: verify_otp.php?email=" . urlencode($safe_email) . "&resent=1");
        exit;
    } else {
        $error = "OTP resend failed: $result";
    }
}

// SUCCESS MESSAGE
if (isset($_GET['resent']) && $_GET['resent'] == 1) {
    $success_message = "New OTP has been sent to your email.";
}

// OTP VERIFICATION
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }
    $otp_entered = trim($_POST['otp']);
    $form_email = trim($_POST['email']);

    $pending_user = $_SESSION['pending_user'] ?? null;

    if (!$pending_user || $pending_user['email'] !== $form_email) {
        $error = "Session expired or invalid email.";
    } else {
        $real_otp = (string)$pending_user['otp_code']; // string cast to match input
        $expiry = strtotime($pending_user['otp_expiration']);

        if ($real_otp !== $otp_entered) {
            $error = "Invalid OTP. Please check again.";
        } elseif ($expiry < time()) {
            $error = "OTP has expired. Please resend.";
        } else {
            // OTP is valid, proceed with file move and DB insert
            $con = get_db_connection();
            $secure_dir = 'D:/xampp/secure_uploads/';
            $temp_dir = 'D:/xampp/temp_uploads/';

            if (!is_dir($secure_dir)) mkdir($secure_dir, 0755, true);

            $pic_path = $temp_dir . $pending_user['profile_picture'];
            $file_path = $temp_dir . $pending_user['file_upload'];
            $new_pic_path = $secure_dir . $pending_user['profile_picture'];
            $new_file_path = $secure_dir . $pending_user['file_upload'];

            $pic_moved = file_exists($pic_path) && rename($pic_path, $new_pic_path);
            $file_moved = file_exists($file_path) && rename($file_path, $new_file_path);

            if (!$pic_moved || !$file_moved) {
                $error = "File move failed. Try again.";
                if ($pic_moved) unlink($new_pic_path);
                if ($file_moved) unlink($new_file_path);
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO info (username, email, password, url, tel, dob, volume, age, gender, skills, department, profile_picture, file_upload, color, feedback, is_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");

                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssssssiissssss",
                        $pending_user['username'], $pending_user['email'], $pending_user['password'],
                        $pending_user['url'], $pending_user['tel'], $pending_user['dob'], $pending_user['volume'],
                        $pending_user['age'], $pending_user['gender'], $pending_user['skills'],
                        $pending_user['department'], $pending_user['profile_picture'], $pending_user['file_upload'],
                        $pending_user['color'], $pending_user['feedback']
                    );
                    if (mysqli_stmt_execute($stmt)) {
                        unset($_SESSION['pending_user']);
                        mysqli_stmt_close($stmt);
                        mysqli_close($con);
                        header("Location: user_login.php?registered=1");
                        exit;
                    } else {
                        $error = "DB error. Try again.";
                        unlink($new_pic_path);
                        unlink($new_file_path);
                    }
                } else {
                    $error = "DB prepare error.";
                    unlink($new_pic_path);
                    unlink($new_file_path);
                }
            }
        }
    }
}
?>
<!-- HTML below (unchanged) -->
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .otp-container { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px #ccc; width: 300px; }
        .otp-container h2 { text-align: center; }
        .otp-container input { width: 100%; padding: 10px; margin: 10px 0; }
        .otp-container button { padding: 10px; width: 100%; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .otp-container button:hover { background: #0056b3; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; }
        .resend-link { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
<div class="otp-container">
    <h2>Email Verification</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success_message)): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php else: ?>
        <p>An OTP has been sent to <strong><?= htmlspecialchars($email) ?></strong>.</p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <label>Enter OTP:</label>
        <input type="text" name="otp" required maxlength="6">
         <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
        <button type="submit">Verify</button>
    </form>

    <div class="resend-link">
        <a href="verify_otp.php?email=<?= urlencode($email) ?>&resend=1">Resend OTP?</a>
    </div>
</div>
</body>
</html>
