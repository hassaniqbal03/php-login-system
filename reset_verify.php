<?php
session_start(); // THIS MUST BE THE VERY FIRST LINE
require_once 'db.php';
require_once 'mail_helper.php'; // Ensure this path is correct
require_once 'csrf_helper.php';
// Generate CSRF token for the form
$csrf_token = generate_csrf_token(); 
$error = '';
$success_message = '';
$session = $_SESSION['forgot'] ?? null;
$email_for_display = $_GET['email'] ?? ($session['email'] ?? ''); // Get email for display


// --- Check if user is currently blocked (session-based or DB-based from forgot_password.php) ---
if (isset($_SESSION['otp_block_until']) && time() < $_SESSION['otp_block_until']) {
    header("Location: block_notice.php");
    exit;
}

// Resend OTP logic for forgot password
// Ensure this part is only executed for GET requests with 'resend=1'
if (isset($_GET['resend']) && $_GET['resend'] == 1 && $session && $_SERVER["REQUEST_METHOD"] === "GET") {
    // Check if enough time has passed since last block for resend
    $con = get_db_connection();
    $stmt_check_block = mysqli_prepare($con, "SELECT otp_block_until FROM info WHERE email = ?");
    mysqli_stmt_bind_param($stmt_check_block, 's', $session['email']);
    mysqli_stmt_execute($stmt_check_block);
    mysqli_stmt_bind_result($stmt_check_block, $db_block_until);
    mysqli_stmt_fetch($stmt_check_block);
    mysqli_stmt_close($stmt_check_block);
    mysqli_close($con);

    $db_block_until_timestamp = strtotime($db_block_until ?? '1970-01-01');

    if ($db_block_until_timestamp > time()) {
        $_SESSION['otp_block_until'] = $db_block_until_timestamp; // Re-set session block if still blocked
        header("Location: block_notice.php");
        exit;
    }


    $safe_email = filter_var($session['email'], FILTER_SANITIZE_EMAIL);
    $safe_username = preg_replace('/[^a-zA-Z0-9 _\-]/', '', $session['username'] ?? 'User');

    $new_otp = rand(100000, 999999);
    $new_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // Update session with new OTP and reset attempts
    $_SESSION['forgot']['otp_code'] = $new_otp;
    $_SESSION['forgot']['otp_expiration'] = $new_expiry;
    $_SESSION['forgot']['attempts'] = 0; // Reset attempts on successful resend

    $result = send_otp_email($safe_email, $safe_username, $new_otp, true);
    if ($result === true) {
        // Redirect to clear the 'resend' parameter from the URL and show success message
        header("Location: reset_verify.php?email=" . urlencode($safe_email) . "&resent=1");
        exit;
    } else {
        $error = "Failed to resend OTP: " . $result;
    }
}

// Display success message if redirected after a resend
if (isset($_GET['resent']) && $_GET['resent'] == 1) {
    $success_message = "A new OTP has been sent to your email.";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }
    $otp_entered = trim($_POST['otp'] ?? '');
    $form_email = trim($_POST['email'] ?? ''); // Get email from hidden form field

    // Re-fetch session data, as it might have changed after resend or initial load
    $session = $_SESSION['forgot'] ?? null;

    // --- Critical Validation Checks ---
    if (!$session || !isset($session['email']) || $session['email'] !== $form_email) {
        // This condition covers: session lost, session incomplete, or email mismatch
        $error = "Session expired or invalid email. Please start the forgot password process again.";
    } elseif (empty($otp_entered) || !is_numeric($otp_entered) || strlen($otp_entered) !== 6) {
        $error = "Invalid OTP format. It must be a 6-digit number.";
    } elseif ($otp_entered !== (string)$session['otp_code'] || strtotime($session['otp_expiration']) < time()) {
        // OTP mismatch or expired
        $error = "Incorrect or expired OTP.";
        $_SESSION['forgot']['attempts']++; // Increment attempts on failure

        $max_attempts = 3;
        $block_duration_minutes = 3; // Block for 3 minutes

        if ($_SESSION['forgot']['attempts'] >= $max_attempts) {
            $_SESSION['otp_block_until'] = time() + ($block_duration_minutes * 60); // Set block time in session

            // Also update DB for persistent block if user keeps failing
            $con = get_db_connection();
            $stmt_update_db_block = mysqli_prepare($con, "UPDATE info SET otp_attempts = ?, otp_block_until = ? WHERE email = ?");
            if ($stmt_update_db_block) {
                $block_time_db_format = date("Y-m-d H:i:s", $_SESSION['otp_block_until']);
                mysqli_stmt_bind_param($stmt_update_db_block, "iss", $max_attempts, $block_time_db_format, $form_email);
                mysqli_stmt_execute($stmt_update_db_block);
                mysqli_stmt_close($stmt_update_db_block);
            }
            mysqli_close($con);

            header("Location: block_notice.php");
            exit;
        } else {
             $remaining = $max_attempts - $_SESSION['forgot']['attempts'];
             $error .= " You have " . $remaining . " attempt(s) left.";
        }
    } else {
        // OTP is correct!
        // Clear attempts and block status from session and DB
        unset($_SESSION['forgot']['attempts']);
        unset($_SESSION['otp_block_until']);

        $con = get_db_connection();
        $stmt_reset_db_attempts = mysqli_prepare($con, "UPDATE info SET otp_attempts = 0, otp_block_until = NULL WHERE email = ?");
        if ($stmt_reset_db_attempts) {
            mysqli_stmt_bind_param($stmt_reset_db_attempts, "s", $session['email']);
            mysqli_stmt_execute($stmt_reset_db_attempts);
            mysqli_stmt_close($stmt_reset_db_attempts);
        }
        mysqli_close($con);

        // Proceed to password reset page
        header("Location: reset_password.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ... your styles ... */
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
    <h2>OTP Verification (Password Reset)</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success_message)): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php else: ?>
        <p>An OTP has been sent to your email <strong><?= htmlspecialchars($email_for_display) ?></strong>.</p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email_for_display) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

        <label for="otp">Enter OTP:</label>
        <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" title="Please enter a 6-digit OTP">
        <button type="submit">Verify</button>
    </form>

    <div class="resend-link">
        <a href="reset_verify.php?email=<?= urlencode($email_for_display) ?>&resend=1">Resend OTP?</a>
    </div>
</div>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error!',
    text: '<?= htmlspecialchars($error) ?>',
    timer: 4000,
    showConfirmButton: false
});
</script>
<?php endif; ?>
<?php if (!empty($success_message)): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '<?= htmlspecialchars($success_message) ?>',
    timer: 3000,
    showConfirmButton: false
});
</script>
<?php endif; ?>
</body>
</html>