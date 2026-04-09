<?php
// This is the register/login page functionality
session_start();
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use ParagonIE\AntiCSRF\AntiCSRF;

$csrf = new AntiCSRF(); 
if (!$csrf->validateRequest()) { 
    die("Invalid CSRF token. Request blocked."); 
} 

// If you are registering (click the register button)
if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_retype = $_POST['retype_password'];

    // Make sure the email isn't already registered
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $check_email = $stmt->get_result();

    if($check_email->num_rows > 0){
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
    }
    // Make sure the password verification is good
    elseif($password != $password_retype){
        $_SESSION['retype_error'] = 'Passwords do not match';
        $_SESSION['active_form'] = 'register';
    }
    // If everything is good then add the user to the database
    else{
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $name, $email, $password);
        $stmt->execute();
        $_SESSION['account_confirmation'] = 'Account successfully created';
    }

    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// This is for when the user clicks the login button
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            header("Location: " . BASE_URL . "/src/views/main_menu.php");
            exit();
        }
    }

    // If its wrong, then warning
    $_SESSION['login_error'] = 'Incorrect email or password';
    $_SESSION['active_form'] = 'login';
    header("Location: " . BASE_URL . "/index.php");
    exit();
}
?>