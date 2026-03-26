<?php
require 'api_config.php';
require 'login_page_config.php';
session_start();
$userId = $_SESSION['user_id'];
date_default_timezone_set('America/Toronto');
$today = date('l');
$today_date = date('Y-m-d');

if (isset($_POST['add_calories'])) {
    $calories = intval($_POST['calories_added']);
    $add_stmt = $conn->prepare("UPDATE user_daily_calories SET total_calories = total_calories + ? WHERE user_id = ? AND log_date = ?");
    $add_stmt->bind_param('iis', $calories, $userId, $today_date);
    $add_stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['remove_calories'])) {
    $calories = intval($_POST['calories_removed']);
    $remove_stmt = $conn->prepare("UPDATE user_daily_calories SET total_calories = total_calories - ? WHERE user_id = ? AND log_date = ?");
    $remove_stmt->bind_param('iis', $calories, $userId, $today_date);
    $remove_stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$check_today_row = $conn->prepare("SELECT log_date, total_calories FROM user_daily_calories WHERE user_id = ?");
$check_today_row->bind_param('i', $userId);
$check_today_row->execute();
$existing_row = $check_today_row->get_result()->fetch_assoc();

$scheduled_calories_query = "SELECT COALESCE(SUM(r.calories), 0) AS total
               FROM meal_schedule ms
               JOIN recipes r ON ms.recipe_id = r.recipe_id
               WHERE ms.user_id = ? AND ms.day_of_week = ?";
$scheduled_calories_stmt = $conn->prepare($scheduled_calories_query);
$scheduled_calories_stmt->bind_param('is', $userId, $today);
$scheduled_calories_stmt->execute();
$scheduled_calories = $scheduled_calories_stmt->get_result()->fetch_assoc();

if (!$existing_row) {
    $insert_row = $conn->prepare("INSERT INTO user_daily_calories (user_id, log_date, total_calories) VALUES (?, ?, ?)");
    $insert_row->bind_param('isi', $userId, $today_date, $scheduled_calories['total']);
    $insert_row->execute();
} elseif ($existing_row['log_date'] !== $today_date) {
    $reset_row = $conn->prepare("UPDATE user_daily_calories SET log_date = ?, total_calories = ? WHERE user_id = ?");
    $reset_row->bind_param('sii', $today_date, $scheduled_calories['total'], $userId);
    $reset_row->execute();
}

$get_total_calories = $conn->prepare("SELECT total_calories FROM user_daily_calories WHERE user_id = ? AND log_date = ?");
$get_total_calories->bind_param('is', $userId, $today_date);
$get_total_calories->execute();
$total_calories = $get_total_calories->get_result()->fetch_assoc()['total_calories'];

$get_goal = $conn->prepare("SELECT daily_goal FROM calorie_goals WHERE user_id = ?");
$get_goal->bind_param('i', $userId);
$get_goal->execute();
$goal_row = $get_goal->get_result()->fetch_assoc();
$current_goal = $goal_row['daily_goal'] ?? 0;

$prompt = "User ate $total_calories/$current_goal kcal today. Give a short motivational message + 1 tip. Be warm and concise.";
$data = [
    "model" => "claude-haiku-4-5-20251001",
    "max_tokens" => 1000,
    "messages" => [
        [
            "role" => "user",
            "content" => $prompt
        ]
    ]
];

$ch = curl_init("https://api.anthropic.com/v1/messages");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-api-key: " . $ANTHROPIC_API_KEY,
    "anthropic-version: 2023-06-01"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
if ($response === false) {
    die("cURL error: " . curl_error($ch));
}

$result = json_decode($response, true);

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