<?php
session_start();
require 'db.php';

if (isset($_GET['email'])) {
    // Step 1: Generate approval token
    $emailToDelete = $_GET['email'];
    $token = bin2hex(random_bytes(32));
    $_SESSION['del_email'] = $emailToDelete;
    $_SESSION['del_token'] = $token;

    // Step 2: Simulate email sending (use real mail() in prod)
    $approveLink = "http://localhost/5thproject/approve_delete.php?token=$token";
    $safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
echo "<h3>User '$safe_email' deleted successfully.</h3>";


} elseif (isset($_GET['token'])) {
    if ($_GET['token'] === ($_SESSION['del_token'] ?? '')) {
        $email = $_SESSION['del_email'];
        $stmt = mysqli_prepare($con, "DELETE FROM info WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo "<h3>User '$email' deleted successfully.</h3>";
        unset($_SESSION['del_token'], $_SESSION['del_email']);
    } else {
        echo "Invalid or expired token.";
    }
} else {
    echo "Invalid access.";
}
