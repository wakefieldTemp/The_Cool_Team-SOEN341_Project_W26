<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_calorie_functions.php';

$userId = $_SESSION['user_id'];

if(isset($_POST['set_goal'])){
    $daily_goal = intval($_POST['daily_goal']);
    setGoal($userId, $daily_goal);
}


$current_goal = getCurrentGoal($userId);

?>