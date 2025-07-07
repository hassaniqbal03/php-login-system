<?php
session_start(); // THIS MUST BE THE VERY FIRST LINE
require_once 'db.php';
require_once 'mail_helper.php'; // OTP email bhejane ke liye
require_once 'csrf_helper.php';
// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }
    $email = trim($_POST['email'] ?? '');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $con = get_db_connection();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email exists in database
        $stmt = mysqli_prepare($con, "SELECT username, otp_block_until FROM info WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $username, $db_block_until);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            $block_until_timestamp = strtotime($db_block_until ?? '1970-01-01');

            if (isset($username)) { // User found
                // Check if user is currently blocked
                if ($block_until_timestamp > time()) {
                    $_SESSION['otp_block_until'] = $block_until_timestamp; // block_notice.php expects this
                    mysqli_close($con);
                    header("Location: block_notice.php");
                    exit;
                }

                $otp = rand(100000, 999999);
                $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes")); // OTP valid for 5 minutes

                // Store info in session for verification
                $_SESSION['forgot'] = [
                    'email' => $email,
                    'username' => $username, // Username bhi store karein email ke liye
                    'otp_code' => $otp,
                    'otp_expiration' => $expiry,
                    'attempts' => 0 // Initialize attempts for this reset session
                ];

                // Send OTP email
                $send = send_otp_email($email, $username, $otp); // Mail helper function
                if ($send === true) {
                    mysqli_close($con);
                    header("Location: reset_verify.php?email=" . urlencode($email)); // Pass email for display
                    exit;
                } else {
                    $error = "OTP sending failed: " . $send;
                }
            } else {
                $error = "Email is not registered  !"; // "Email not registered!"
            }
        } else {
             $error = "An unexpected error occurred. Please try again later.";
        }
    }
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <script src="[https://cdn.jsdelivr.net/npm/sweetalert2@11](https://cdn.jsdelivr.net/npm/sweetalert2@11)"></script>
    <style>
        body { font-family: Arial; background: #eef; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px #ccc;
            width: 350px;
        }
        h2 { text-align: center; }
        input[type="email"], button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border: 1px solid #aaa;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: center; margin-top: 10px; }
        .success { color: green; text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
<div class="box">
    <h2>Forgot Password</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Enter Your Registered Email:</label>
        <input type="email" id="email" name="email" required>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
        <button type="submit">SEND OTP </button>
    </form>
    <div style="text-align: center; margin-top: 15px;">
        <a href="user_login.php">Back To Login Page</a>
    </div>
</div>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error!',
    text: '<?= htmlspecialchars($error) ?>',
    timer: 3000,
    showConfirmButton: false
});
</script>
<?php endif; ?>
</body>
</html>