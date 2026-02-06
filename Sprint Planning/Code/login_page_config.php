<?php
$host = "localhost";
$user = "root";
$password = "";   // empty for XAMPP on Mac usually
$database = "users_db"; // or whatever database name you created in phpMyAdmin

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
