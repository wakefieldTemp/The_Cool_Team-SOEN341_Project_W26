<?php
session_start();
require_once __DIR__ . '/config/login_page_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use ParagonIE\AntiCSRF\AntiCSRF;
$csrf = new AntiCSRF();

// Different type of errors we can expect
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
    'retype' => $_SESSION['retype_error'] ?? ''
];

// Different confirmations we can expect (we only have one rn D;)
$confirmations = [
    'creation' => $_SESSION['account_confirmation'] ?? ''
];

// To switch between the registration and login form
$activeForm = $_SESSION['active_form'] ?? 'login';

session_unset();

// This function shows the error
function showError($error){
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

// This function shows the confirmation (legit same thing as error)
function showConfirmation($confirmation){
    return !empty($confirmation) ? "<p class='confirmation-message'>$confirmation</p>" : '';
}

// This is for the current active form (login or register)
function isActiveForm($formName, $activeForm){
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang ="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Cool Team's Website</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/login_page_style.css">
</head>

<body>

    <header class="site-header">
        <h1 class="site-title">The Cool Team App</h1>
    </header>

    <div class="container">
        <!-- This is the login form (we see the isActiveForm function call, this is to see which is active, so which one we're displaying) -->
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="<?= BASE_URL ?>/src/controllers/login_page_register.php" method="post">
                <?= $csrf->insertToken() ?>
                <h2>Login</h2>
                <?= showError($errors['login']);?>
                <?= showConfirmation($confirmations['creation']);?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Not already signed up? Sign up <a href="#" onclick="showForm('register-form')">here</a></p>
            </form>
        </div>
        <!-- This is the registration form -->
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="<?= BASE_URL ?>/src/controllers/login_page_register.php" method="post">
                <?= $csrf->insertToken() ?>
                <h2>Register</h2>
                <?= showError($errors['register']); ?>
                <?= showError($errors['retype']); ?>
                <input type="name" name="name" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="retype_password" placeholder="Confirm password" required>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? Login <a href="#" onclick="showForm('login-form')">here</a></p>
            </form>
        </div>
    </div>

<script src="<?= BASE_URL ?>/public/js/login_page_script.js"></script>
</body>

</html>