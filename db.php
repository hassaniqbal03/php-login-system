
<?php
// Add this function if it does not exist
function get_db_connection() {
    $servername = "localhost";
    $username = "root";
    $password = ""; // update if you have a password
    $dbname = "form_submission"; // update with your actual database name

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
