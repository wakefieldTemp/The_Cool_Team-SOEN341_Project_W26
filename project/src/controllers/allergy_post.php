<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_allergy_functions.php';
$userId = $_SESSION['user_id'];
// If we add an allergy (add allergy button as clicked)
if(isset($_POST['add_allergy'])) {
    $allergy_name = trim($_POST['allergy_name']);
    addAllergy($allergy_name, $userId);
}

// Simply delete the allergy (if delete allergy button is pressed)
if(isset($_POST['delete_allergy'])) {
    $allergy_id = $_POST['allergy_id'];
    deleteAllergy($allergy_id, $userId);
}

$allergies = getAllergies($userId);
?>