<?php
session_start();
require_once 'auth_helper.php';
require_once 'db.php';
require_once 'csrf_helper.php'; // Include CSRF helper

$admin_data = is_admin_logged_in();
if (!$admin_data) {
    header("Location: user_login.php?error=unauthorized_access");
    exit;
}

$error_message = '';
$user_id_to_delete = 0; // Initialize

// Handle POST request for actual deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF Token Validation - Yahan lagana hai
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF attack detected or token mismatch on admin delete for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: all_users.php?error=" . urlencode("Security check failed. Please try again."));
        exit;
    }

    $user_id_to_delete = (int)($_POST['user_id_to_delete'] ?? 0); // Get ID from POST

    if ($user_id_to_delete <= 0) {
        $error_message = "Invalid user ID for deletion.";
    } else {
        $conn = get_db_connection();
        $secure_upload_dir = "D:/xampp/secure_uploads/";

        try {
            $conn->begin_transaction();

            // 1. Fetch file paths
            $sql_fetch_files = "SELECT profile_picture, file_upload FROM info WHERE id = ?";
            $stmt_fetch = $conn->prepare($sql_fetch_files);
            $stmt_fetch->bind_param("i", $user_id_to_delete);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();
            $files_to_delete = $result_fetch->fetch_assoc();
            $stmt_fetch->close();

            // 2. Delete user record
            $sql_delete_user = "DELETE FROM info WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete_user);
            $stmt_delete->bind_param("i", $user_id_to_delete);

            if ($stmt_delete->execute()) {
                // 3. Delete associated files
                if ($files_to_delete) {
                    if (!empty($files_to_delete['profile_picture']) && file_exists($secure_upload_dir . $files_to_delete['profile_picture'])) {
                        unlink($secure_upload_dir . $files_to_delete['profile_picture']);
                    }
                    if (!empty($files_to_delete['file_upload']) && file_exists($secure_upload_dir . $files_to_delete['file_upload'])) {
                        unlink($secure_upload_dir . $files_to_delete['file_upload']);
                    }
                }
                $conn->commit();
                $stmt_delete->close();
                header("Location: all_users.php?deleted=1");
                exit;
            } else {
                $conn->rollback();
                $stmt_delete->close();
                $error_message = "Failed to delete user from database: " . $stmt_delete->error;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Database error: " . $e->getMessage();
        }
        $conn->close();
    }
} 
// Handle GET request to display confirmation form
else {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid user ID for deletion request.");
    }
    $user_id_to_delete = (int)$_GET['id'];
    // You might want to fetch user details here to display "Are you sure you want to delete [username]?"
}

// Generate CSRF token for the form that will be displayed
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Admin Delete</title>
    </head>
<body>
    <div class="container">
        <h2>Confirm User Deletion</h2>
        <?php if (!empty($error_message)): ?>
            <p style="color: red;">
                <?php
                // Show a generic error if the message contains sensitive details (e.g., "Database error")
                if (stripos($error_message, 'Database error') !== false || stripos($error_message, 'Failed to delete user from database') !== false) {
                    echo "An unexpected error occurred. Please try again later.";
                } else {
                    // Only show generic error for unexpected errors, otherwise show sanitized message
                    if (stripos($error_message, 'Database error') !== false || stripos($error_message, 'Failed to delete user from database') !== false) {
                        echo "An unexpected error occurred. Please try again later.";
                    } else {
                        echo "An error occurred. Please contact the administrator.";
                    }
                }
                ?>
            </p>
        <?php endif; ?>
        <p>Are you sure you want to delete user ID: <strong><?= htmlspecialchars($user_id_to_delete) ?></strong>? This action cannot be undone.</p>
        
        <form method="POST">
            <input type="hidden" name="user_id_to_delete" value="<?= htmlspecialchars($user_id_to_delete); ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>"> <button type="submit">Yes, Delete User</button>
            <a href="all_users.php">No, Go Back</a>
        </form>
    </div>
</body>
</html>