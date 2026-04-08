<?php
session_start();
require_once __DIR__ . '/../../config/api_config.php';
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_calorie_functions.php';
require_once __DIR__ . '/../models/api_calorie_functions.php';
$userId = $_SESSION['user_id'];
date_default_timezone_set('America/Toronto');
$today = date('l');
$today_date = date('Y-m-d');

if (isset($_POST['add_calories'])) {
    $calories = intval($_POST['calories_added']);
    addCalories($conn, $userId, $calories, $today_date);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['remove_calories'])) {
    $calories = intval($_POST['calories_removed']);
    removeCalories($conn, $userId, $calories, $today_date);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}


$scheduled_calories = getScheduledCalories($conn, $userId, $today);
checkCalories($conn, $userId, $today_date, $scheduled_calories);
$total_calories = getTotalCalories($conn, $userId, $today_date);
$current_goal = getDailyGoal($conn, $userId);

$result = getCalorieTip($total_calories, $current_goal);

if (!isset($result['content'][0]['text'])) {
    die("<pre>API Error: " . htmlspecialchars($response) . "</pre>");
}

$tip = $result['content'][0]['text'];
$tip = preg_replace('/^```(?:json)?\s*/i', '', trim($tip));
$tip = preg_replace('/\s*```$/', '', $tip);
?>