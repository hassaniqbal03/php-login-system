<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';
require_once 'csrf_helper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF Token FIRST
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        // Token invalid or missing. Log this for security monitoring.
        error_log("CSRF attack detected or token mismatch for IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again.")); // Or a generic error page
        exit;
    }

    $email = trim($_POST['email'] ?? '');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'] ?? '';
    $password = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

    if (empty($email) || empty($password)) {
        header("Location: user_login.php?error=Email and password required");
        exit;
    }

    $con = get_db_connection();

    $stmt = $con->prepare("SELECT id, email, password, role, is_verified FROM info WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            header("Location: user_login.php?error=Incorrect password");
            exit;
        }

        if ($user['is_verified'] != 1) {
            header("Location: user_login.php?error=Please verify your email before login.");
            exit;
        }

        // ✅ New: Generate and store a new session_id_version on successful login
        $new_session_version = bin2hex(random_bytes(32)); // Generate a new unique token

        $update_stmt = $con->prepare("UPDATE info SET session_id_version = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_session_version, $user['id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Store this version in the user's current session
        $_SESSION['user'] = [
            'id'                 => $user['id'],
            'email'              => $user['email'],
            'role'               => $user['role'],
            'session_id_version' => $new_session_version // Store in session
        ];

        // JWT create (continue with your existing JWT logic)
        $token = generate_jwt($user['id'], $user['email'], $user['role']);
        set_auth_cookie($token); // Set the auth cookie

        // Optional: Remember email
        if (isset($_POST['remember'])) {
            $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
            setcookie("remember_email", $email, [
                'expires'  => time() + (86400 * 7),
                'path'     => '/',
                'secure'   => !$is_localhost,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        } else {
            setcookie("remember_email", "", time() - 3600, "/");
        }

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: dashboard_admin.php?login_success=1");
        } else {
            header("Location: dashboard_user.php?just_logged_in=1");
        }
        exit;

    } else {
        header("Location: user_login.php?error=User not found");
        exit;
    }
} else {
    header("Location: user_login.php");
    exit;
}
?>