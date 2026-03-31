<?php
// This is the register/login page functionality
session_start();
require_once __DIR__ . '/../../config/login_page_config.php';

// If you are registering (click the register button)
if(isset($_POST['register'])){
    // Take the values that are typed
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_retype = $_POST['retype_password'];

    // Make sure the email isn't already registered
    $check_email = $conn->query("SELECT email FROM users WHERE email = '$email'");
    if($check_email->num_rows > 0){
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
    }
    // Make sure the password verification is good
    elseif($password != $password_retype){
        $_SESSION['retype_error'] = 'Passwords do not match';
        $_SESSION['active_form'] = 'register';
    }
    // If everything is good than add the user to the database
    else{
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')");
        // Confirmation message to show that the account was created
        $_SESSION['account_confirmation'] = 'Account successfully created';
    }

    header("Location: " . BASE_URL . "/index.php");
    exit();
}

// This is for when the user clicks the login button
if(isset($_POST['login'])){
    // Get the values
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Make sure the user exist in the database (check for the email)
    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        // If the password is correct then log him in (set the sessions values to the user's values)
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