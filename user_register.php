<?php
session_start();
require_once 'csrf_helper.php';
// Agar user already logged in hai
if (isset($_SESSION['user'])) {
    header("Location: dashboard_user.php");
    exit;
}
if (isset($_SESSION['pending_user'])) {
    header("Location: verify_otp.php?email=" . urlencode($_SESSION['pending_user']['email']));
    exit;
}
// Check agar user block hai
if (isset($_SESSION['otp_block_until']) && time() < $_SESSION['otp_block_until']) {
    header("Location: block_notice.php?source=login"); // Source parameter to block_notice
    exit;
}
// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
?>// 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>HTML Form</title>
  <link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
     <form action="submit.php" method="POST" enctype="multipart/form-data">

    <fieldset>
      <legend>Personal Details</legend>

      <div class="form-group">
        <label for="username">Name:</label><br>
        <input type="text" id="username" name="username" placeholder="Enter your name" maxlength="26" required /> <br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" placeholder="Enter your email" required /><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" placeholder="Enter your password" maxlength="8" required /><br>
      
        <label for="url">Website:</label><br>
        <input type="url" id="url" name="url" placeholder="Paste your URL" /><br>
        <label for="telephone">Telephone:</label><br>
        <input type="tel" id="telephone" name="telephone" placeholder="Enter your phone number" maxlength="11" /><br>

        <label for="dob">Date of Birth:</label><br>
        <input type="date" id="dob" name="dob" /><br>
      </div>
    </fieldset>

    <fieldset>
      <legend>Numeric and Date Inputs</legend>

      <div class="inputs-groups">
        <label for="volume">Volume Control:</label><br>
        <input type="range" id="volume" name="volume" min="0" max="100" /><br>

        <label for="age">Age:</label><br>
        <input type="number" id="age" name="age" max="130" /><br>

      </div>
    </fieldset>

    <fieldset>
      <legend>Gender</legend>
      <div class="radio-group">
        <label><input type="radio" id="male" name="gender" value="Male" required /> Male</label>
        <label><input type="radio" id="female" name="gender" value="Female" required /> Female</label>
        <label><input type="radio" id="other" name="gender" value="Other" /> Other</label>
      </div>
    </fieldset>

    <fieldset>
      <legend>Skills</legend>
      <div class="checkbox-group">
        <label><input type="checkbox" name="skills[]" value="HTML" />HTML</label>
        <label><input type="checkbox" name="skills[]" value="CSS" /> CSS</label>
        <label><input type="checkbox" name="skills[]" value="JavaScript" /> JavaScript</label>
        <label><input type="checkbox" name="skills[]" value="PHP" /> PHP</label>
        <label><input type="checkbox" name="skills[]" value="Laravel" /> Laravel</label>
      </div>
    </fieldset>

    <fieldset>
      <legend>Department</legend>
      <div class="option-group">
        <label for="department">Department:</label>
        <select name="department" id="department" required>
          <option value="">Select Department</option>
          <option value="Development">Development</option>
          <option value="Design">Design</option>
          <option value="Marketing">Marketing</option>
          <option value="HR">HR</option>
        </select>
      </div>
    </fieldset>

    <fieldset>
      <legend>Upload Files</legend>

      <div class="Files-group">
        <label for="profile_picture">Profile Picture:</label><br>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required /><br>

        <label for="file">Choose Files:</label><br>
        <input type="file" id="file" name="file_upload" required />
      </div>
    </fieldset>

    <fieldset>
      <legend>Color Selection</legend>
      <div class="color-group">
        <label for="color">Favorite Color:</label>
        <input type="color" name="color" id="color" />
      </div>
    </fieldset>

    <fieldset>
      <legend>Feedback</legend>
      <div class="feedback">
        <textarea name="feedback" id="feedback" rows="8" placeholder="Please give your feedback"></textarea>
      </div>
    </fieldset>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token); ?>">

    <div class="button">
      <input type="submit" value="Submit" />
    </div>
   <!--  Login Redirect Button -->
<div class="button" style="margin-top: 10px;">
  <button type="button" onclick="window.location.href='user_login.php'" style="
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
  ">
    Already Registered? Login
  </button>
</div>
  </form>
  <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Registration Complete!',
        text: 'Your account has been created successfully.',
        showConfirmButton: false,
        timer: 2500
    });

    setTimeout(() => {
        window.location.href = 'user_login.php';
    }, 2500);
</script>
<?php endif; ?>

  <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Account Deleted!',
        text: 'Your account and files have been successfully removed.',
        confirmButtonColor: '#3085d6'
    });
</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Validation Error',
    text: '<?= htmlspecialchars($_GET["error"]) ?>',
    showConfirmButton: true
});
</script>
<?php endif; ?>

</body>
</html>