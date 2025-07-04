<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwt_secret_key = "my_super_secret_key";

// ===== Generate JWT Token =====
function generate_jwt($user_id, $email, $role) {
    global $jwt_secret_key;

    $issuedAt = time();
    $expirationTime = $issuedAt + 120; // 2 minutes

    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'data' => [
            'id' => $user_id,
            'email' => $email,
            'role' => $role
        ]
    ];

    return JWT::encode($payload, $jwt_secret_key, 'HS256');
}

// ===== Decode and Verify JWT Token =====
function decode_jwt($token) {
    global $jwt_secret_key;

    try {
        return JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
    } catch (Exception $e) {
        return null;
    }
}

// ===== Set Secure Cookie for JWT =====
function set_auth_cookie($token) {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    setcookie("auth_token", $token, [
        'expires' => time() + 600,
        'path' => '/',
        'secure' => !$is_localhost,   
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// ===== Clear Cookie on Logout =====
function clear_auth_cookie() {
    $is_localhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    setcookie("auth_token", "", [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => !$is_localhost,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// ===== Check if Admin is Logged In =====
function is_admin_logged_in() {
    if (!isset($_COOKIE['auth_token'])) {
        return false;
    }

    $decoded = decode_jwt($_COOKIE['auth_token']);
    if (!$decoded || $decoded->data->role !== 'admin') {
        return false;
    }

    return $decoded->data;
}

// ===== Check if Any User is Logged In =====
function is_user_logged_in() {
    if (!isset($_COOKIE['auth_token'])) {
        return false;
    }

    $decoded = decode_jwt($_COOKIE['auth_token']);
    if (!$decoded) {
        return false;
    }

    return $decoded->data;
}
