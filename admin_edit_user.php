<?php
session_start();
require_once 'auth_helper.php'; // JWT helper
require_once 'db.php';         // Database connection function

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

// Fetch user data for pre-filling the form
$sql = "SELECT * FROM info WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // User data found, proceed to display form
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
    <title>Edit User - Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 20px; }
        .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); max-width: 800px; margin: 20px auto; }
        h2 { color: #2c3e50; text-align: center; margin-bottom: 30px; }
        form { display: grid; grid-template-columns: 1fr; gap: 15px; }
        label { font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="email"], input[type="url"], input[type="tel"],
        input[type="number"], input[type="date"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
        }
        input[type="file"] {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f8f8;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            width: 100%;
        }
        input[type="submit"]:hover { background-color: #218838; }
        .gender-options label { font-weight: normal; margin-right: 15px; display: inline-block; }
        .gender-options input[type="radio"] { margin-right: 5px; }
        .current-file { margin-top: 10px; margin-bottom: 15px; }
        .current-file img { max-width: 150px; height: auto; border: 1px solid #ddd; border-radius: 4px; }
        .back-link { display: block; text-align: center; margin-top: 20px; }
        .back-link a {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-link a:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit User: <?= htmlspecialchars($row['username']) ?> (ID: <?= htmlspecialchars($row['id']) ?>)</h2>
        <form action="admin_update_user.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['id']) ?>">
            <input type="hidden" name="old_profile_picture" value="<?= htmlspecialchars($row['profile_picture']) ?>">
            <input type="hidden" name="old_file_upload" value="<?= htmlspecialchars($row['file_upload']) ?>">

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required>
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="user" <?= ($row['role'] === 'user') ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= ($row['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
            </select>

            <label for="url">Website:</label>
            <input type="url" id="url" name="url" value="<?= htmlspecialchars($row['url']) ?>">

            <label for="tel">Phone:</label>
            <input type="tel" id="tel" name="tel" value="<?= htmlspecialchars($row['tel']) ?>">

            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($row['dob']) ?>">

            <label for="volume">Volume (Range 1-100):</label>
            <input type="range" id="volume" name="volume" min="1" max="100" value="<?= htmlspecialchars($row['volume']) ?>">

            <label for="age">Age:</label>
            <input type="number" id="age" name="age" value="<?= htmlspecialchars($row['age']) ?>">

          <label for="gender">Gender:</label>
        <div class="form-group-radio">
            <input type="text" id="gender" name="gender" value="<?= htmlspecialchars($row['gender']) ?>" readonly style="background-color: #e9ecef; cursor: not-allowed; border: 1px solid #ced4da;">
            <p style="font-size: 0.8em; color: #6c757d; margin-top: 5px;">(Gender cannot be changed)</p>
        </div>

            <label for="skills">Skills:</label>
            <input type="text" id="skills" name="skills" value="<?= htmlspecialchars($row['skills']) ?>">

            <label for="department">Department:</label>
            <input type="text" id="department" name="department" value="<?= htmlspecialchars($row['department']) ?>">

            <label for="profile_picture">Profile Picture:</label>
            <?php if (!empty($row['profile_picture'])): ?>
                <div class="current-file">
                    <img src="serve_file.php?file=<?= urlencode($row['profile_picture']) ?>" alt="Current Profile Picture">
                    <p>Current: <?= htmlspecialchars($row['profile_picture']) ?></p>
                </div>
            <?php else: ?>
                <p>No profile picture uploaded.</p>
            <?php endif; ?>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            <small>Upload a new image to replace the current one.</small>

            <label for="file_upload">PDF File:</label>
            <?php if (!empty($row['file_upload'])): ?>
                <div class="current-file">
                    <a href="serve_file.php?file=<?= urlencode($row['file_upload']) ?>" target="_blank">View Current PDF</a>
                    <p>Current: <?= htmlspecialchars($row['file_upload']) ?></p>
                </div>
            <?php else: ?>
                <p>No PDF file uploaded.</p>
            <?php endif; ?>
            <input type="file" id="file_upload" name="file_upload" accept="application/pdf">
            <small>Upload a new PDF to replace the current one.</small>

            <label for="color">Favorite Color:</label>
            <input type="color" id="color" name="color" value="<?= htmlspecialchars($row['color']) ?>">

            <label for="feedback">Feedback:</label>
            <textarea id="feedback" name="feedback" rows="5"><?= htmlspecialchars($row['feedback']) ?></textarea>

            <input type="submit" value="Update User">
        </form>
        <div class="back-link">
            <a href="all_users.php">Back to All Users</a>
        </div>
    </div>
    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'User Profile Updated!',
                text: 'The user\'s profile has been successfully updated by Admin.',
                showConfirmButton: false,
                timer: 2500
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Update Failed!',
                text: '<?= htmlspecialchars($_GET['error']) ?>',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
</body>
</html>