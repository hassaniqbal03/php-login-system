<?php

require_once __DIR__ . '/config.php'; // Yeh assume karta hai ke config.php aur db.php aik hi folder mein hain

function get_db_connection() {
    // Constants jo config.php mein define kiye hain, ab yahan use honge
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
?>