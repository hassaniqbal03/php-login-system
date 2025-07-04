<?php
session_start();
require_once 'auth_helper.php';

session_unset();
session_destroy();

clear_auth_cookie(); 

header("Location: user_login.php?logout=1");
exit;
