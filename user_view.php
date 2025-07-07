<?php
session_start();
require_once 'auth_helper.php';
$user_data = is_user_logged_in(); 
if (!$user_data) { exit; }
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    session_unset();
    session_destroy();
    header("Location: user_login.php?error=Unauthorized");
    exit;
}


require_once 'db.php';
$con = get_db_connection();
// Retrieve email from session
if (isset($_SESSION['user'])) { 
   $email = trim($_SESSION['user']['email']);
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
} else {
    
    echo "Email not provided in session."; 
    exit;
}



$sql = "SELECT * FROM info WHERE email = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Detail</title>
    <!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            background-color: #f5f5f5;
        }

        .user-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); /* Adjusted shadow for consistency */
            width: 400px;
            margin: 0 auto; /* Center the card */
        }

        .user-card img {
            border-radius: 4px;
            margin-top: 10px;
            max-width: 150px; /* Added max-width for image */
            height: auto;
            display: block; /* Ensures image is on its own line */
            margin-bottom: 10px; /* Space below image */
        }

        .user-card strong {
            color: #333;
        }

        .user-card br {
            margin-bottom: 8px; /* Spacing between lines */
        }

        .btn {
            display: inline-block;
            margin: 10px 5px 0 0;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-dashboard {
            background-color: #dc3545;
        }

        .btn-dashboard:hover {
            background-color: #b52a3a;
        }
    </style>
</head>
<body>
    <div class="user-card">
        <h2>User Details</h2>
        <strong>Name:</strong> <?= htmlspecialchars($row['username']) ?><br>
        <strong>Email:</strong> <?= htmlspecialchars($row['email']) ?><br>
        <strong>URL:</strong> <?= htmlspecialchars($row['url']) ?><br>
        <strong>Phone:</strong> <?= htmlspecialchars($row['tel']) ?><br>
        <strong>DOB:</strong> <?= htmlspecialchars($row['dob']) ?><br>
        <strong>Age:</strong> <?= htmlspecialchars($row['age']) ?><br>
        <strong>Gender:</strong> <?= htmlspecialchars($row['gender']) ?><br>
        <strong>Skills:</strong> <?= htmlspecialchars($row['skills']) ?><br>
        <strong>Department:</strong> <?= htmlspecialchars($row['department']) ?><br>
        <strong>Profile Pic:</strong><br>
      <?php
      if (!empty($row['profile_picture'])) {
     echo '<img src="serve_file.php?file=' . urlencode($row['profile_picture']) . '" alt="Profile Picture" width="150">';
      } else {
       echo 'No profile picture available.<br>';
        }
    ?>

     <strong>PDF File:</strong>
     <?php
     if (!empty($row['file_upload'])) {
   echo "<a href='serve_file.php?file=" . urlencode($row['file_upload']) . "' target='_blank'>View PDF</a>";
     } else {
     echo 'No File';
     }
     ?><br>

        <strong>Color:</strong> <?= htmlspecialchars($row['color']) ?><br>
        <strong>Feedback:</strong> <?= htmlspecialchars($row['feedback']) ?><br>

        <br>
        <a href="user_edit.php?email=<?= htmlspecialchars($row['email']) ?>" class="btn">Edit</a>
        <a href="dashboard_user.php?email=<?= htmlspecialchars($row['email']) ?>" class="btn btn-dashboard">Dashboard</a>
        
    </div>
    <?php if (isset($_GET['login_success']) && $_GET['login_success'] == 1): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Login Successful!',
    text: 'Welcome back!',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php endif; ?>
</body>
</html>
<?php
} else {
    echo "No record found for this email.";
}

mysqli_stmt_close($stmt);
mysqli_close($con);
?>