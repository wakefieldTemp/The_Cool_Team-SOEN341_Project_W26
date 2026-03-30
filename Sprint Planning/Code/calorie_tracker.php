<?php
require 'api_config.php';
require 'login_page_config.php';
require 'sql_calorie_functions.php';
require 'api_calorie_functions.php';
session_start();
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

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calorie Tracker</title>
    <link rel="stylesheet" href="calorie_tracker_style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="introduction">
        <h1>Welcome to your calorie tracker!</h1>
        <p>Here you can track your daily calorie intake depending on today's meal plan.</p>
        <p>You can add extra calories you consume, or remove calories</p>
    </div>
    <div class="back-button">
        <a href="main_menu.php">
            <i class='bx bx-arrow-back'></i> Back to Main Menu
        </a>
    </div>
    <div class="calories">
        <h2>Calorie Intake</h2>
        <p>Total Calories: <span id="total-calories"><?php echo $total_calories; ?>/<?php echo $current_goal; ?></span></p>
        <form method="POST" class="add-calories">
            <input type="number" name="calories_added" placeholder="Calories to add" required>
            <button type="submit" name="add_calories">Add Calories</button>
        </form>
        <form method="POST" class="remove-calories">
            <input type="number" name="calories_removed" placeholder="Calories to remove" required>
            <button type="submit" name="remove_calories">Remove Calories</button>
        </form>
    </div>
    <div class="tip">
        <?php echo $tip; ?>
    </div>
</body>
</html>