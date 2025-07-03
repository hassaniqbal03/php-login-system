<?php
session_start();
require_once 'auth_helper.php';
require_once 'db.php';

// Verify if the user is an admin via JWT
$admin_data = is_admin_logged_in();
if (!$admin_data) {
    header("Location: user_login.php?error=unauthorized_access");
    exit;
}

$conn = get_db_connection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid user ID.");
}

$user_id = (int)$_GET['id'];

// Fetch user data
$sql = "SELECT * FROM info WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // User data found
} else {
    die("User not found.");
}

$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User Details - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 600px; margin: 20px auto; }
        h2 { color: #333; text-align: center; margin-bottom: 25px; }
        p { margin-bottom: 10px; line-height: 1.6; }
        strong { color: #555; }
        img { max-width: 150px; height: auto; border-radius: 5px; margin-top: 10px; border: 1px solid #ddd; }
        .btn-group { text-align: center; margin-top: 30px; }
        .btn-group a {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-group a:hover { background-color: #0056b3; }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-edit:hover { background-color: #e0a800; }
        .btn-delete { background-color: #dc3545; }
        .btn-delete:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Details (Admin View)</h2>
        <p><strong>ID:</strong> <?= htmlspecialchars($row['id']) ?></p>
        <p><strong>Username:</strong> <?= htmlspecialchars($row['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($row['role']) ?></p>
        <p><strong>Website:</strong> <?= htmlspecialchars($row['url']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($row['tel']) ?></p>
        <p><strong>Date of Birth:</strong> <?= htmlspecialchars($row['dob']) ?></p>
        <p><strong>Volume:</strong> <?= htmlspecialchars($row['volume']) ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($row['age']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($row['gender']) ?></p>
        <p><strong>Skills:</strong> <?= htmlspecialchars($row['skills']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($row['department']) ?></p>
        <p><strong>Color:</strong> <?= htmlspecialchars($row['color']) ?></p>
        <p><strong>Feedback:</strong> <?= htmlspecialchars($row['feedback']) ?></p>

        <p><strong>Profile Pic:</strong><br>
        <?php if (!empty($row['profile_picture'])): ?>
            <img src='serve_file.php?file=<?= urlencode($row['profile_picture']) ?>' alt="Profile Picture" width='150'>
        <?php else: ?>
            No profile picture available.<br>
        <?php endif; ?>
        </p>
        

        <p><strong>PDF File:</strong>
        <?php if (!empty($row['file_upload'])): ?>
            <a href='serve_file.php?file=<?= urlencode($row['file_upload']) ?>' target='_blank'>View PDF</a>
        <?php else: ?>
            No PDF file available.
        <?php endif; ?>
        </p>

        <div class="btn-group">
            <a href="all_users.php">Back to All Users</a>
            <a href="admin_edit_user.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn-edit">Edit User</a>
            <a href="admin_delete_user.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</a>
        </div>
    </div>
</body>
</html>