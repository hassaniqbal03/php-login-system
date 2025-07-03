<?php
session_start();
require_once 'db.php';
require_once 'auth_helper.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: user_login.php?error=Email and password required");
        exit;
    }

    $con = get_db_connection();

    $stmt = $con->prepare("SELECT id, email, password, role FROM info WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // JWT create
            $token = generate_jwt($user['id'], $user['email'], $user['role']);

            //  Securely set the auth cookie (localhost or HTTPS)
            set_auth_cookie($token);

            //  Handle remember email (optional)
            if (isset($_POST['remember'])) {
                $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
                setcookie("remember_email", $email, [
                    'expires' => time() + (86400 * 7),
                    'path' => '/',
                    'secure' => !$is_localhost,
                    'httponly' => false,
                    'samesite' => 'Lax'
                ]);
            } else {
                // Unset if not checked
                setcookie("remember_email", "", time() - 3600, "/");
            }

            //  Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: all_users.php");
            } else {
                header("Location: dashboard.php");
            }

            exit;
        } else {
            header("Location: user_login.php?error=Incorrect password");
            exit;
        }
    } else {
        header("Location: user_login.php?error=User not found");
        exit;
    }
} else {
    header("Location: user_login.php?error=Invalid request");
    exit;
}
