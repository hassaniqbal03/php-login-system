<!-- user editt function -->
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location:user_login.php");
    exit;
}

$server = "127.0.0.1:3306";
$username = "root";
$password = "";
$database = "form_submission";

$con = mysqli_connect($server, $username, $password, $database);
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    die("Invalid or missing email.");
}

$email = $_GET['email'];
$sql = "SELECT * FROM info WHERE email = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Form</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        form {
            background: white;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        input[type="text"],
        input[type="email"],
        input[type="url"],
        input[type="dob"],
        input[type="age"],
        input[type="file"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        label {
            font-weight: bold;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        img {
            display: block;
            margin-bottom: 10px;
            max-width: 120px;
            height: auto;
        }
    </style>
</head>
<body>

<form method="POST" action="update.php" enctype="multipart/form-data">
    <input type="hidden" name="email" value="<?= htmlspecialchars($row['email']) ?>">

    <label>Name:</label>
    <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>">

    <label>URL:</label>
    <input type="url" name="url" value="<?= htmlspecialchars($row['url']) ?>">

    <label>Phone:</label>
    <input type="text" name="tel" value="<?= htmlspecialchars($row['tel']) ?>">

    <label>Age:</label>
    <input type="text" name="age" value="<?= htmlspecialchars($row['age']) ?>">

    <label>Skills:</label>
    <input type="text" name="skills" value="<?= htmlspecialchars($row['skills']) ?>">

    <label>Department:</label>
    <input type="text" name="department" value="<?= htmlspecialchars($row['department']) ?>">

    <label>Profile Picture:</label>
    <?php
    if (!empty($row['profile_picture'])) {
        echo '<img src="serve_file.php?file=' . urlencode($row['profile_picture']) . '" alt="Current Picture">';
    } else {
        echo '<p>No image uploaded.</p>';
    }
    ?>
    <input type="file" name="profile_picture">

    <label>PDF File:</label>
    <?php
    if (!empty($row['file_upload'])) {
        echo '<a href="serve_file.php?file=' . urlencode($row['file_upload']) . '" target="_blank">View Existing File</a><br>';
    } else {
        echo '<p>No file uploaded.</p>';
    }
    ?>
    <input type="file" name="file_upload">

    <label>Feedback:</label>
    <textarea name="feedback"><?= htmlspecialchars($row['feedback']) ?></textarea>

    <input type="submit" value="Update">
</form>
<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Profile Updated!',
    text: 'Your details were successfully updated.',
    showConfirmButton: false,
    timer: 2500
});

setTimeout(() => {
    window.location.href = 'user_view.php?email=<?= urlencode($_GET["email"]) ?>';
}, 2500);
</script>
<?php endif; ?>

</body>
</html>
<?php
} else {
    echo "Record not found.";
}
?>
