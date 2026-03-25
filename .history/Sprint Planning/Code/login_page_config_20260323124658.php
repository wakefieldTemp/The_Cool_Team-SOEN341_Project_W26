<?php
// Simple config file (connects to the database), don't know why it's called login_page_config, can be used everywhere
$host = "localhost";
$user = "root";
$password = "";   // empty for XAMPP on Mac usually
$database = "users_db"; // or whatever database name you created in phpMyAdmin

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// This function shows the error
//function showError($error){
//    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
//}

?>
