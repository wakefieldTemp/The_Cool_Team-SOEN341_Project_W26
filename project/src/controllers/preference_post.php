<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_preference_functions.php';

$userId = $_SESSION['user_id'];

// Exact same thing as allergies just for the diet preferences
if(isset($_POST['add_preference'])) {
    $preference_name = trim($_POST['preference_name']);
    addPreference($userId, $preference_name);
}

if(isset($_POST['delete_preference'])) {
    $preference_id = $_POST['preference_id'];
    deletePreference($preference_id, $userId);
}
$preferences = getPreferences($userId);
?>