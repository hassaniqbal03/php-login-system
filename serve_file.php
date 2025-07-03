<?php
session_start();
require_once 'auth_helper.php'; // JWT and session check

// Allow both user session and admin JWT
if (!is_user_logged_in()) {
    http_response_code(403);
    die("Access Denied: Login required.");
}

if (!isset($_GET['file']) || empty($_GET['file'])) {
    http_response_code(400);
    die("Koi file specify nahi ki gayi.");
}

$filename = basename($_GET['file']);
$filepath = "D:/xampp/secure_uploads/" . $filename; 

if (!file_exists($filepath)) {
    http_response_code(404);
    die("File nahi mili.");
}

$mime_type = mime_content_type($filepath);
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

//  Allowed types (aapka naya logic)
$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowed_doc_types = ['application/pdf'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

if (
    (!in_array($mime_type, $allowed_image_types) && !in_array($mime_type, $allowed_doc_types)) ||
    !in_array($ext, $allowed_extensions)
) {
    http_response_code(403);
    die("Invalid ya unsupported file type.");
}

header("Content-Type: " . $mime_type);
header("Content-Length: " . filesize($filepath));
header("Content-Disposition: inline; filename=\"" . basename($filename) . "\"");

readfile($filepath);

exit;