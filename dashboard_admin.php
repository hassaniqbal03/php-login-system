<?php
session_start();

require_once 'auth_helper.php'; // JWT helper ko include karein

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in();


if (!$admin_data) {
    clear_auth_cookie(); 
    header("Location: user_login.php?error=session_expired");
    exit;
}


// Database connection
require_once 'db.php';
$conn = get_db_connection();

// --- Admin Dashboard Statistics (Example) ---
// Total Users
$stmt_total_users = $conn->prepare("SELECT COUNT(id) AS total_users FROM info");
$stmt_total_users->execute();
$result_total_users = $stmt_total_users->get_result();
$total_users = $result_total_users->fetch_assoc()['total_users'];
$stmt_total_users->close();

// Admins Count
$stmt_total_admins = $conn->prepare("SELECT COUNT(id) AS total_admins FROM info WHERE role = 'admin'");
$stmt_total_admins->execute();
$result_total_admins = $stmt_total_admins->get_result();
$total_admins = $result_total_admins->fetch_assoc()['total_admins'];
$stmt_total_admins->close();

// Regular Users Count
$total_regular_users = $total_users - $total_admins;

$conn->close(); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: #333;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 900px;
            text-align: center;
            margin-bottom: 30px;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
            font-weight: 600;
        }
        p {
            font-size: 1.1em;
            line-height: 1.6;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .stat-card {
            background-color: #e0f7fa;
            border-left: 5px solid #00bcd4;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: left;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            color: #00796b;
            margin-top: 0;
            font-size: 1.4em;
        }
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: #004d40;
            margin-bottom: 0;
        }
        .btn-group { margin-top: 20px; }
        .btn-group a {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-size: 1.1em;
        }
        .btn-group a:hover { background-color: #218838; }
        .btn-logout { background-color: #dc3545; }
        .btn-logout:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Dashboard</h2>
     <p style="text-align: center; font-size: 1.1em;">
    Welcome, Admin! (<?= htmlspecialchars($admin_data['email']) ?>)
</p>


        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Registered Users</h3>
                <p><?= htmlspecialchars($total_users) ?></p>
            </div>
            <div class="stat-card">
                <h3>Admin Accounts</h3>
                <p><?= htmlspecialchars($total_admins) ?></p>
            </div>
            <div class="stat-card">
                <h3>Regular Users</h3>
                <p><?= htmlspecialchars($total_regular_users) ?></p>
            </div>
        </div>

        <div class="btn-group">
            <a href="all_users.php">View All Users</a>
            <a href="logout.php" class="btn-logout">Logout</a>
            <a href="forgot_password.php" class="btn-password">Change Password</a>
        </div>
    </div>
    
    <?php if (isset($_GET['login_success']) && $_GET['login_success'] == 1): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Admin Login Successful!',
        text: 'Welcome to the Admin Dashboard.',
        showConfirmButton: false,
        timer: 2500
    });
    </script>
    <?php endif; ?>

</body>
</html>