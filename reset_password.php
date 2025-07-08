<?php
session_start();
require_once 'db.php';
require_once 'csrf_helper.php'; // CSRF helper file ko include karein

$session = $_SESSION['forgot'] ?? null;

// Agar session['forgot'] set nahi hai, toh user ko forgot password page par bhej dein
if (!$session) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //  CSRF Token Validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF attack detected or token mismatch on password reset for IP: " . $_SERVER['REMOTE_ADDR']);
        // Yahan session data clear karna zaroori hai agar invalid request hai
        unset($_SESSION['forgot']); 
        session_unset();
        session_destroy();
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please try again."));
        exit;
    }

    $pass = trim($_POST['password']);
    $cpass = trim($_POST['confirm_password']);

    // Password validation
    if ($pass !== $cpass) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_@!])[A-Za-z\d_@!]{8,}$/', $pass)) {
        $error = "Password must be at least 8 chars, include letter, number & symbol (_@!).";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $con = get_db_connection();

        //  New: Generate a new session_id_version
        $new_session_version = bin2hex(random_bytes(32)); 

        // Password aur session_id_version dono ko database mein update karein
        $stmt = mysqli_prepare($con, "UPDATE info SET password = ?, session_id_version = ? WHERE email = ?");
        // 'sss' for string, string, string (password, session_id_version, email)
        mysqli_stmt_bind_param($stmt, 'sss', $hash, $new_session_version, $session['email']);

        if (mysqli_stmt_execute($stmt)) {
            // 'forgot' session data ko clear karein kyuki process complete ho gaya
            unset($_SESSION['forgot']);

            //  Important: Check karein ki user current session mein logged in hai aur uski email reset kiye gaye email se match karti hai
            if (isset($_SESSION['user']) && $_SESSION['user']['email'] === $session['email']) {
                // Agar user logged in hai, toh uske current session ki session_id_version ko update karein
                $_SESSION['user']['session_id_version'] = $new_session_version;
                
                // Role ke hisaab se dashboard par redirect karein
                if ($_SESSION['user']['role'] === 'admin') {
                    header("Location: dashboard_admin.php?password_changed=1"); // Assuming you have dashboard_admin.php
                } else {
                    header("Location: dashboard_user.php?password_changed=1"); // Assuming you have dashboard_user.php
                }
                exit;
            } else {
                // Agar user logged in nahi tha (normal forgot password flow), toh login page par bhej dein
                header("Location: user_login.php?reset=1");
                exit;
            }
        } else {
            // Log the detailed error for debugging, but show a generic message to the user
            error_log("Password reset DB error for " . $session['email'] . ": " . mysqli_error($con));
            $error = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
        mysqli_close($con);
    }
}

//  CSRF token generate karein form ke liye
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e8f0fe; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            box-sizing: border-box; /* Padding and border included in element's total width and height */
        }
        h2 { text-align: center; color: #333; margin-bottom: 20px;}
        input[type="password"], button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border: 1px solid #aaa;
            border-radius: 5px;
            box-sizing: border-box; /* Ensures padding doesn't increase width */
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background: #0056b3;
        }
        .error { color: red; text-align: center; margin-top: 10px; font-size: 0.9em; }
        .success { color: green; text-align: center; margin-top: 10px; font-size: 0.9em; } /* Agar success message bhi display karna ho */
    </style>
</head>
<body>
<div class="box">
    <h2>Reset Password</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="password" name="password" placeholder="New Password" required minlength="8">
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>"> <button type="submit">Reset Password</button>
    </form>
</div>
</body>
</html>