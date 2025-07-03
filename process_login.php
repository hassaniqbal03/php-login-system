<?php
session_start();

require_once 'db.php';          // Updated to use the function-based db connection
require_once 'auth_helper.php'; // New helper file for JWT

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if (empty($email)) {
        header("Location: user_login.php?error=empty_email");
        exit;
    }

    $password_input = trim($_POST['password']);

    // Get database connection
    $conn = get_db_connection();

    // Assuming 'info' is your user table and it has a 'role' column
    $sql = "SELECT id, email, password, role FROM info WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password_input, $row['password'])) {
            // Password is correct
            
            // Check user role
            if ($row['role'] === 'admin') {
                // Generate JWT for admin
                $jwt_token = generate_jwt($row['id'], $row['email'], $row['role']);
           
                // Here, using JWT's default 24 hours (86400 seconds * 1 day)
                set_jwt_cookie($jwt_token, time() + (3600 * 24)); // 24 hours expiration

                // Clear any existing user session to prevent role confusion
                session_unset();
                session_destroy();
                session_start(); // Start new session for potential future use or immediate redirect

                // Redirect admin to admin dashboard with success flag
                // Close resources before redirect
                $stmt->close();
                $conn->close();
                header("Location: dashboard_admin.php?login_success=1"); // Added ?login_success=1
                exit;

            } else {
                // For regular users, continue with session-based login
                $_SESSION['user'] = $row['email'];
                $_SESSION['user_role'] = $row['role']; // Store role in session as well

                if (isset($_POST['remember'])) {
                    setcookie("remember_email", $email, time() + (86400 * 7), "/"); // 7 days
                } else {
                    setcookie("remember_email", "", time() - 3600, "/"); // Unset cookie
                }

                // Close resources before redirect
                $stmt->close();
                $conn->close();
                // Redirect regular user to their dashboard or view page with success flag
                header("Location: dashboard_user.php?login_success=1"); // Added ?login_success=1
                exit;
            }
        } else {
            // Invalid password
            $stmt->close();
            $conn->close();
            header("Location: user_login.php?error=invalid_credentials");
            exit;
        }
    } else {
        // User not found
        $stmt->close();
        $conn->close();
        header("Location: user_login.php?error=invalid_credentials");
        exit;
    }

} else {
    // Not a POST request, redirect to login page
    header("Location: user_login.php");
    exit;
}
?>