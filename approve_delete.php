<?php
session_start();
require 'db.php';
require_once 'csrf_helper.php'; // Include CSRF helper

// Handle the GET request with token to display the confirmation form
if (isset($_GET['token'])) {
    if ($_GET['token'] === ($_SESSION['del_token'] ?? '')) {
        // Token matches session token, now display the confirmation form
        $email_to_confirm_delete = $_SESSION['del_email'] ?? '';
        $csrf_token = generate_csrf_token(); // Generate CSRF for this form
        
        // Display HTML form for POST confirmation
        echo '<h2>Confirm Deletion</h2>';
        echo '<p>Are you sure you want to delete account for: ' . htmlspecialchars($email_to_confirm_delete) . '?</p>';
        echo '<form method="POST">';
        echo '<input type="hidden" name="confirm_token_from_email" value="' . htmlspecialchars($_GET['token']) . '">'; // Pass email token
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">'; // ✅ CSRF Hidden Field
        echo '<button type="submit">Yes, Delete</button>';
        echo '</form>';
        exit;
    } else {
        echo "Invalid or expired confirmation link.";
        exit;
    }
} 
// Handle the POST request after form submission
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_token_from_email'])) {
    // ✅ CSRF Token Validation - Yahan lagana hai
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF attack detected or token mismatch on email approval delete for IP: " . $_SERVER['REMOTE_ADDR']);
        echo "Security check failed. Please try again.";
        exit;
    }

    // Validate the email confirmation token from POST against session
    if ($_POST['confirm_token_from_email'] === ($_SESSION['del_token'] ?? '')) {
        $email = $_SESSION['del_email'] ?? '';
        if (!empty($email)) {
            $con = get_db_connection();
            $stmt = mysqli_prepare($con, "DELETE FROM info WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            echo "<h3>User '" . htmlspecialchars($email) . "' deleted successfully.</h3>";
            unset($_SESSION['del_token'], $_SESSION['del_email']);
        } else {
            echo "Error: Email not found in session for deletion.";
        }
    } else {
        echo "Invalid or expired confirmation token.";
    }
}
// ... rest of the original logic if any, e.g., for initial email sending ...
else {
    // This part handles the initial request to send the email (if it's not already in POST)
    // Make sure this initial email sending part is also secure (e.g., requires admin login, CSRF if it's a form submission)
    echo "Invalid request or link."; // Or redirect to an appropriate page
}
?>