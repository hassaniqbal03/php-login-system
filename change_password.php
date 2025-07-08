<?php
session_start();
require_once 'auth_helper.php'; // For is_user_logged_in() and session_id_version logic
require_once 'db.php';         // For database connection
require_once 'csrf_helper.php'; // For CSRF token generation and validation

// 1. Verify if the user is logged in
$user_data = is_user_logged_in(); // This function handles JWT, $_SESSION, and session_id_version validation

// If not logged in, redirect to the login page.

if (!$user_data) {
    // If auth_helper.php did not redirect, do it here.
   
    header("Location: user_login.php?error=" . urlencode("Please log in to change your password."));
    exit;
}

$error = '';
$success = '';

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Validate CSRF token FIRST
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        error_log("CSRF attack detected or token mismatch on change password for IP: " . $_SERVER['REMOTE_ADDR'] . " User ID: " . ($user_data['id'] ?? 'N/A'));
        
        // Log out the user immediately if CSRF token is invalid
        session_unset();
        session_destroy();
        clear_auth_cookie(); // Also clear the JWT cookie
        
        header("Location: user_login.php?error=" . urlencode("Security check failed. Please log in again."));
        exit;
    }

    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_new_password = trim($_POST['confirm_new_password'] ?? '');

    // 3. Basic input validation
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_new_password) {
        $error = "New passwords do not match.";
    } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[_@!])[A-Za-z\d_@!]{8,}$/', $new_password)) {
        // Password strength validation (same as registration/reset)
        $error = "New password must be at least 8 characters long, including letters, numbers, and symbols (_@!).";
    } else {
        $con = get_db_connection();

        // 4. Verify current password against the stored hash in the database
        $stmt = $con->prepare("SELECT password FROM info WHERE id = ?");
        $stmt->bind_param("i", $user_data['id']); // Use ID from validated session data
        $stmt->execute();
        $result = $stmt->get_result();
        $db_user_password_info = $result->fetch_assoc();
        $stmt->close();

        if (!$db_user_password_info || !password_verify($current_password, $db_user_password_info['password'])) {
            $error = "Incorrect current password. Please try again.";
        } else {
            // 5. Hash the new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            // 6. Generate a new session_id_version to invalidate other sessions
            $new_session_version = bin2hex(random_bytes(32));

            // 7. Update password AND session_id_version in the database
            $update_stmt = $con->prepare("UPDATE info SET password = ?, session_id_version = ? WHERE id = ?");
            // 'ssi' for string (hashed password), string (new session_id_version), integer (user ID)
            $update_stmt->bind_param("ssi", $hashed_new_password, $new_session_version, $user_data['id']);

            if ($update_stmt->execute()) {
                // 8. Update the current session with the new session_id_version
                // This ensures the current session remains valid
                $_SESSION['user']['session_id_version'] = $new_session_version;
                
                $success = "Your password has been changed successfully!";
            

            } else {
                error_log("Password change DB update error for User ID " . $user_data['id'] . ": " . mysqli_error($con));
                $error = "Failed to update password. Please try again later.";
            }
            $update_stmt->close();
        }
        mysqli_close($con); // Close DB connection
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc); /* Gradient background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px; /* Slightly wider for labels */
            text-align: center;
            box-sizing: border-box; /* Include padding in width */
        }
        h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2em;
            font-weight: 600;
        }
        label {
            display: block;
            text-align: left;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            font-size: 0.95em;
        }
        input[type="password"] {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
        }
        button {
            width: 100%;
            padding: 15px;
            background-color: #28a745; /* Green button */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-sizing: border-box;
        }
        button:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 0.95em;
        }
        .error {
            background-color: #ffe0e0;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }
        .success {
            background-color: #e0f7fa;
            color: #00796b;
            border: 1px solid #00796b;
        }
        a {
            display: block;
            margin-top: 25px;
            color: #007bff;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="message error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="message success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password" maxlength="8">

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password" maxlength="8">

            <label for="confirm_new_password">Confirm New Password</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="8" autocomplete="new-password" maxlength="8">
            
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">
            
            <button type="submit">Change Password</button>
        </form>

        <a href="dashboard_<?php echo htmlspecialchars($user_data['role']); ?>.php">Back to Dashboard</a>
    </div>

    <?php if (!empty($error) || !empty($success)): ?>
    <script>
    Swal.fire({
        icon: '<?= empty($error) ? "success" : "error" ?>',
        title: '<?= empty($error) ? "Success!" : "Error!" ?>',
        text: '<?= empty($error) ? htmlspecialchars($success, ENT_QUOTES, 'UTF-8') : htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>',
        showConfirmButton: true,
        timer: <?= empty($error) ? 3000 : 5000 ?> // Shorter timer for success
    });
    </script>
    <?php endif; ?>
</body>
</html>