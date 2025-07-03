<?php
// auth_helper.php
// Handles JWT creation and validation

require_once __DIR__ . '/vendor/autoload.php'; // Composer autoloader
require_once 'db.php'; // Your database connection function

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Secret key for JWT signing - load from a file for security
function get_jwt_secret_key() {
    // Path to your secret key file
    $key_file = __DIR__ . '/admin_token.key';
    if (!file_exists($key_file)) {
        error_log("JWT Secret Key file not found: " . $key_file);
        die("Server configuration error: JWT key missing.");
    }
    return trim(file_get_contents($key_file));
}

// Function to generate a JWT
function generate_jwt($user_id, $user_email, $user_role) {
    $secret_key = get_jwt_secret_key();
    $issued_at = time();
    $expiration_time = $issued_at + (3600 * 24); // Token valid for 24 hours (adjust as needed)

    $payload = array(
        'iat' => $issued_at,         // Issued at
        'exp' => $expiration_time,   // Expiration time
        'data' => [
            'id' => $user_id,
            'email' => $user_email,
            'role' => $user_role
        ]
    );

    return JWT::encode($payload, $secret_key, 'HS256');
}

// Function to validate a JWT
function validate_jwt($jwt_token) {
    $secret_key = get_jwt_secret_key();
    try {
        $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));
        return (array) $decoded->data; // Return user data if valid
    } catch (Exception $e) {
        // Log the error for debugging, but don't expose sensitive info to user
        error_log("JWT Validation Error: " . $e->getMessage());
        return false; // Token is invalid or expired
    }
}

// Function to check if a user is logged in as admin via JWT
function is_admin_logged_in() {
    if (isset($_COOKIE['admin_jwt'])) {
        $jwt = $_COOKIE['admin_jwt'];
        $decoded_data = validate_jwt($jwt);

        if ($decoded_data && isset($decoded_data['role']) && $decoded_data['role'] === 'admin') {
            return $decoded_data; // Return admin data
        }
    }
    return false;
}

// Function to check if a user is logged in (session or JWT for admin)
function is_user_logged_in() {
    if (isset($_SESSION['user'])) {
        // Session-based user login
        return [
            'email' => $_SESSION['user'],
            'role' => 'user' // Assume session users are 'user' role by default
        ];
    } elseif (is_admin_logged_in()) {
        // Admin logged in via JWT
        $admin_data = is_admin_logged_in();
        return [
            'email' => $admin_data['email'],
            'role' => 'admin'
        ];
    }
    return false;
}

// Function to redirect to login if not logged in
function require_login() {
    if (!is_user_logged_in()) {
        header("Location: user_login.php"); // Redirect to your login page
        exit;
    }
}

// Function to redirect to login if not admin
function require_admin_login() {
    $user_data = is_admin_logged_in();
    if (!$user_data) {
        header("Location: user_login.php?error=unauthorized"); // Redirect to login with error
        exit;
    }
}

// Function to set HttpOnly secure cookie for JWT
function set_jwt_cookie($jwt_token, $expiration_time) {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
    
    setcookie(
        'admin_jwt',
        $jwt_token,
        [
            'expires' => $expiration_time,
            'path' => '/',
            'httponly' => true,
            'secure' => !$is_localhost, // ✅ Secure only in production
            'samesite' => 'Lax'
        ]
    );
}

function clear_jwt_cookie() {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
    
    setcookie(
        'admin_jwt',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => !$is_localhost, // ✅ Match above setting
            'samesite' => 'Lax'
        ]
    );
}
?>