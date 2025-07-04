/* This PHP script is responsible for deleting a user record from a database along with any associated
files stored on the server. Here is a breakdown of what the script does: */
<?php
session_start();
require_once 'auth_helper.php'; // JWT helper
require_once 'db.php';         // Database connection function

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in();
if (!$admin_data) {
    header("Location: user_login.php?error=unauthorized_access");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID for deletion.");
}

$user_id = (int)$_GET['id'];
$conn = get_db_connection();
$secure_upload_dir = "D:/xampp/secure_uploads/"; // Your secure upload directory

try {
    // Start transaction for atomicity
    $conn->begin_transaction();

    // 1. Fetch file paths associated with the user before deleting the record
    $sql_fetch_files = "SELECT profile_picture, file_upload FROM info WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch_files);
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $files_to_delete = $result_fetch->fetch_assoc();
    $stmt_fetch->close();

    // 2. Delete the user record from the database
    $sql_delete_user = "DELETE FROM info WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete_user);
    $stmt_delete->bind_param("i", $user_id);

    if ($stmt_delete->execute()) {
        // 3. Delete associated files from the server
        if ($files_to_delete) {
            if (!empty($files_to_delete['profile_picture']) && file_exists($secure_upload_dir . $files_to_delete['profile_picture'])) {
                unlink($secure_upload_dir . $files_to_delete['profile_picture']);
            }
            if (!empty($files_to_delete['file_upload']) && file_exists($secure_upload_dir . $files_to_delete['file_upload'])) {
                unlink($secure_upload_dir . $files_to_delete['file_upload']);
            }
        }
        $conn->commit(); // Commit transaction if all successful
        $stmt_delete->close();
        header("Location: all_users.php?deleted=1");
        exit;
    } else {
        $conn->rollback(); // Rollback on error
        $stmt_delete->close();
        header("Location: all_users.php?error=" . urlencode("Failed to delete user from database: " . $stmt_delete->error));
        exit;
    }

} catch (Exception $e) {
    $conn->rollback(); // Rollback on any exception
    error_log("User deletion error: " . $e->getMessage());
    header("Location: all_users.php?error=" . urlencode("An unexpected error occurred during deletion."));
    exit;
} finally {
    $conn->close();
}
?>