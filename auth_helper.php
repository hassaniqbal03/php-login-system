<?php
// auth_helper.php

require_once 'vendor/autoload.php';
require_once 'db.php'; // Ensure db.php is included to use get_db_connection()

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// IMPORTANT: Change this to a strong, unique key for production!
// You can generate a strong key using: bin2hex(random_bytes(32)) or more.
$jwt_secret_key = "your_very_strong_and_secret_jwt_key_here_12345"; 

// ===== Generate JWT Token =====
function generate_jwt($user_id, $email, $role) {
    global $jwt_secret_key;

    $issuedAt = time();
    // JWT expiration time. This should generally be short (e.g., 5-15 minutes)
    // and combined with a refresh token mechanism if long sessions are needed.
    // For simplicity, keeping it at 2 minutes as per your original code.
    $expirationTime = $issuedAt + 120; // 2 minutes

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => [
            'id'    => $user_id,
            'email' => $email,
            'role'  => $role
        ]
    ];

    return JWT::encode($payload, $jwt_secret_key, 'HS256');
}

// ===== Decode and Verify JWT Token =====
function decode_jwt($token) {
    global $jwt_secret_key;

    try {
        // The last parameter 'HS256' specifies the algorithm
        return JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
    } catch (Exception $e) {
        // Log the error for debugging, but don't expose to the user
        error_log("JWT decoding error: " . $e->getMessage());
        return null;
    }
}

// ===== Set Secure Cookie for JWT =====
function set_auth_cookie($token) {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    setcookie("auth_token", $token, [
        'expires'  => time() + 600, // Cookie expires in 10 minutes (matching JWT exp for simplicity here, but can be longer)
        'path'     => '/',
        'secure'   => !$is_localhost, // Use 'secure' in production (HTTPS)
        'httponly' => true,           // Prevents JavaScript access
        'samesite' => 'Lax'           // Protects against some CSRF attacks
    ]);
}

// ===== Clear Cookie on Logout =====
function clear_auth_cookie() {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    setcookie("auth_token", "", [
        'expires'  => time() - 3600, // Set expiry in the past to delete
        'path'     => '/',
        'secure'   => !$is_localhost,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// ===== Check if User is Logged In and Session is Validated via session_id_version =====
function is_user_logged_in() {
    // 1. Check if user data exists in the session
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || !isset($_SESSION['user']['session_id_version'])) {
        // If not, try to validate from JWT if available
        if (isset($_COOKIE['auth_token'])) {
            $decoded_jwt = decode_jwt($_COOKIE['auth_token']);
            if ($decoded_jwt && isset($decoded_jwt->data->id) && isset($decoded_jwt->data->email) && isset($decoded_jwt->data->role)) {
                // If JWT is valid, fetch session_id_version from DB and populate session
                $con = get_db_connection();
                $stmt = $con->prepare("SELECT id, email, role, session_id_version FROM info WHERE id = ?");
                $stmt->bind_param("i", $decoded_jwt->data->id);
                $stmt->execute();
                $result = $stmt->get_result();
                $db_user = $result->fetch_assoc();
                $stmt->close();
                mysqli_close($con);

                if ($db_user) {
                    // Populate session with data from DB, including session_id_version
                    $_SESSION['user'] = [
                        'id'                 => $db_user['id'],
                        'email'              => $db_user['email'],
                        'role'               => $db_user['role'],
                        'session_id_version' => $db_user['session_id_version']
                    ];
                    return $_SESSION['user']; // User is now logged in via JWT and session populated
                }
            }
        }
        // If no session or no valid JWT, user is not logged in
        return false;
    }

    // 2. If session data exists, validate against the database's session_id_version
    $con = get_db_connection();
    $stmt = $con->prepare("SELECT session_id_version FROM info WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $db_user_session_info = $result->fetch_assoc();
    $stmt->close();
    mysqli_close($con); // Close connection

    // Check for mismatch or if user no longer exists in DB (e.g., deleted account)
    if (!$db_user_session_info || $db_user_session_info['session_id_version'] !== $_SESSION['user']['session_id_version']) {
        // Mismatch found (password changed, new login elsewhere, or account deleted)
        // Invalidate current session and log out
        session_unset();
        session_destroy();
        clear_auth_cookie(); // Also clear the JWT cookie
        
        // Redirect to login with a message, then exit to prevent further script execution
        header("Location: user_login.php?error=" . urlencode("Your session has expired or been invalidated. Please log in again."));
        exit; 
    }

    // If all checks pass, the user is genuinely logged in and session is valid
    return $_SESSION['user'];
}

// ===== Check if Admin is Logged In =====
function is_admin_logged_in() {
    $user_data = is_user_logged_in(); // Leverage the comprehensive user check
    if ($user_data && $user_data['role'] === 'admin') {
        return $user_data;
    }
    return false;
}

// we can add a function to refresh the JWT token if we want longer sessions
// without making the initial JWT long-lived. This would involve issuing a new JWT
// and setting a new cookie if the current one is nearing expiration.
// function refresh_jwt_token() { ... }

?>