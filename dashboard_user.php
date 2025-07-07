<!-- //user Dashboard -->
<?php
session_start();
require_once 'auth_helper.php'; // Include your auth helper

$user_data = is_user_logged_in(); // This function now handles the session invalidation check
if (!$user_data) {
    header("Location: user_login.php"); 
    exit;
}
if (!isset($_SESSION['user'])) {
    header("Location:user_login.php");
    exit;
}

$email = $_SESSION['user']['email'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right,rgb(235, 180, 116),rgb(21, 21, 22));
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            text-align: center;
            width: 350px;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-logout {
            background-color: #dc3545;
        }

        .btn-logout:hover {
            background-color: #b52a3a;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome!</h2>
        <a class="btn" href="user_edit.php?email=<?= urlencode($email) ?>">✏️ Edit Details</a>
        <a class="btn" href="user_view.php?email=<?= urlencode($email) ?>">👁️ View Profile</a>
      <a class="btn" href="#" id="deleteBtn"> Delete Account</a>
      <a class="btn btn-logout" href="#" id="logoutBtn"> Logout</a>

    </div>

    <!-- Optional: show popup after login -->
    <?php if (isset($_GET['just_logged_in'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful!',
            text: 'Welcome to your dashboard.',
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    <?php endif; ?>
    <script>
    // Logout SweetAlert
    document.getElementById("logoutBtn").addEventListener("click", function(e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to logout!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "logout.php";
            }
        });
    });

    // Delete Account SweetAlert
    document.getElementById("deleteBtn").addEventListener("click", function(e) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Your account will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Replace with actual delete link
                window.location.href = "user_delete.php?email=<?= urlencode($email) ?>";
            }
        });
    });
</script>

</body>
</html>
