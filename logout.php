<?php
session_start();
require_once 'auth_helper.php'; // JWT helper ko include karein

// Clear session variables
session_unset();

// Destroy the session
session_destroy();

// Clear the JWT cookie (if it exists)
clear_jwt_cookie(); // auth_helper.php se function call karein

// Redirect with logout flag
header("Location: user_login.php?logout=1"); // user_login.php par redirect
exit;
?>