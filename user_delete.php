<!-- user Delete function -->
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location:user_login.php");
    exit;
}


require_once 'db.php';
$con = get_db_connection();

if (isset($_GET['email'])) {
    $email = trim($_GET['email']); 
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    $secure_path = "D:/xampp/secure_uploads/";

    $query = "SELECT profile_picture, file_upload FROM info WHERE email = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    $del_sql = "DELETE FROM info WHERE email = ?";
    $del_stmt = mysqli_prepare($con, $del_sql);
    mysqli_stmt_bind_param($del_stmt, "s", $email);

    if (mysqli_stmt_execute($del_stmt)) {
        if (!empty($row['profile_picture']) && file_exists($secure_path . $row['profile_picture'])) {
            unlink($secure_path . $row['profile_picture']);
        }

        if (!empty($row['file_upload']) && file_exists($secure_path . $row['file_upload'])) {
            unlink($secure_path . $row['file_upload']);
        }

        session_unset();
        session_destroy();
       //  DELETE COOKIE
        setcookie("remember_email", "", time() - 3600, "/");
        // Redirect with query param
        header("Location:user_register.php?deleted=1");
        exit;
    } else {
        echo "Delete failed: " . mysqli_stmt_error($del_stmt);
    }

    mysqli_stmt_close($del_stmt);
} else {
    echo "Invalid email.";
    exit;
}

mysqli_close($con);
?>
