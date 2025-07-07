<?php
session_start();
require_once 'csrf_helper.php';
require_once 'db.php'; // DB connection ke liye

// User authentication check
if (!isset($_SESSION['user'])) {
    header("Location: user_login.php");
    exit;
}

$con = get_db_connection();
$error_message = '';
$deletion_successful = false;
$email_to_delete = $_SESSION['user']['email']; // Default to current logged-in user's email

// Handle POST request for actual deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ✅ CSRF Token Validation - POST request mein sabse pehle yahi hoga
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR'] . " on delete attempt.");
        // Security check failed, redirect to dashboard or show error
        header("Location: dashboard_user.php?error=" . urlencode("Security check failed. Please try again."));
        exit;
    }

    // Email to delete should come from a hidden input in the form (POST), not GET
    $email_to_delete = trim($_POST['email_to_delete'] ?? '');

    // Validate email (basic validation, more comprehensive checks might be needed)
    if (empty($email_to_delete) || !filter_var($email_to_delete, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email provided for deletion.";
    } 
    // IMPORTANT: Ensure the user is only deleting THEIR OWN account
    // For a user deleting their own account, ensure the email from the form
    // matches the email in their session.
    elseif ($email_to_delete !== $_SESSION['user']['email']) {
        error_log("Unauthorized delete attempt for email: " . $email_to_delete . " by user: " . $_SESSION['user']['email']);
        header("Location: dashboard_user.php?error=" . urlencode("Unauthorized deletion attempt."));
        exit;
    }
    else {
        $secure_path = "D:/xampp/secure_uploads/"; // Apne path ko sahi karein

        // Fetch file paths before deleting user from DB
        $query = "SELECT profile_picture, file_upload FROM info WHERE email = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $email_to_delete);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $del_sql = "DELETE FROM info WHERE email = ?";
        $del_stmt = mysqli_prepare($con, $del_sql);
        mysqli_stmt_bind_param($del_stmt, "s", $email_to_delete);

        if (mysqli_stmt_execute($del_stmt)) {
            // Delete associated files
            if (!empty($row['profile_picture']) && file_exists($secure_path . $row['profile_picture'])) {
                unlink($secure_path . $row['profile_picture']);
            }
            if (!empty($row['file_upload']) && file_exists($secure_path . $row['file_upload'])) {
                unlink($secure_path . $row['file_upload']);
            }

            // Clear session and cookies
            session_unset();
            session_destroy();
            setcookie("remember_email", "", time() - 3600, "/");

            $deletion_successful = true; // Flag for successful deletion
        } else {
            $error_message = "Delete failed: " . mysqli_stmt_error($del_stmt);
        }
        mysqli_stmt_close($del_stmt);
    }
} 
// Handle GET request (to display confirmation form)
else {
    // We already set $email_to_delete from session at the top.
    // If you want to allow admin to delete other users via GET for confirmation,
    // you would get email from $_GET here: $email_to_delete = trim($_GET['email'] ?? $_SESSION['user']['email']);
    // But then you must add robust checks to ensure the logged-in user (admin) has permission to delete that email.
    // For simplicity, we're assuming a user deletes their own account here.
}

// Generate CSRF token for the form that will be displayed
$csrf_token = generate_csrf_token(); 

mysqli_close($con); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Account</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f8f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); text-align: center; width: 350px; }
        h2 { color: #dc3545; margin-bottom: 20px; }
        p { margin-bottom: 25px; font-size: 1.1em; color: #555; }
        .buttons { display: flex; justify-content: space-around; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 16px; transition: background-color 0.3s ease; }
        .btn-confirm { background-color: #dc3545; color: white; }
        .btn-confirm:hover { background-color: #c82333; }
        .btn-cancel { background-color: #6c757d; color: white; }
        .btn-cancel:hover { background-color: #5a6268; }
        .error { color: red; margin-top: 15px; }
        .success { color: green; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($deletion_successful): ?>
            <p class="success">Your account has been successfully deleted.</p>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Account Deleted!',
                    text: 'Your account has been successfully removed.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = 'user_register.php?deleted=1'; // Redirect after popup
                });
            </script>
        <?php else: ?>
            <h2>Confirm Account Deletion</h2>
            <p>Are you absolutely sure you want to delete your account (<?= htmlspecialchars($email_to_delete) ?>)? This action cannot be undone.</p>
            
            <?php if (!empty($error_message)): ?>
                <p class="error"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="email_to_delete" value="<?= htmlspecialchars($email_to_delete); ?>">
                
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

                <div class="buttons">
                    <button type="submit" class="btn btn-confirm">Yes, Delete My Account</button>
                    <a href="dashboard_user.php" class="btn btn-cancel">No, Go Back</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>