<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location:user_view.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eef2f3;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            width: 320px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #444;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #bbb;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="checkbox"] {
            margin-top: 10px;
        }
input[type="submit"],
.register-btn {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border: none;
    font-size: 16px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease-in-out;
}

input[type="submit"] {
    background-color: #28a745;
    color: white;
}

input[type="submit"]:hover {
    background-color: rgb(136, 50, 33);
}

.register-btn {
    background-color: #007bff;
    color: white;
}

.register-btn:hover {
    background-color:rgb(179, 0, 9);
}


        .remember {
            margin-top: 10px;
            display: flex;
            align-items: center;
        }

        .remember label {
            margin-left: 5px;
            font-weight: normal;
        }
    </style>
    <!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
    <form action="process_login.php" method="POST">
        <h2>Login</h2>
        
        <label>Email:</label>
       <input type="email" name="email" value="<?php echo htmlspecialchars($_COOKIE['remember_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />


        <label>Password:</label>
        <input type="password" name="password" required>

        <div class="remember">
            <input type="checkbox" name="remember" <?= isset($_COOKIE['remember_email']) ? 'checked' : '' ?>>
            <label>Remember Me</label>
        </div>

        <input type="submit" value="Login">
          

        <!-- Register Button -->
     <button type="button" onclick="window.location.href='user_register.php'" class="register-btn">Register Your Account</button>
    </form>
    <!-- SweetAlert2 CDN already included above -->
<?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Registration Successful!',
    text: 'You can now log in to your account.',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php endif; ?>

<?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
<script>
Swal.fire({
    icon: 'info',
    title: 'Logged Out',
    text: 'You have been logged out successfully.',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php endif; ?>

</body>
</html>
