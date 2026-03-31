<?php
function addCalories($conn, $userId, $calories, $today_date) {
    $add_stmt = $conn->prepare("UPDATE user_daily_calories SET total_calories = total_calories + ? WHERE user_id = ? AND log_date = ?");
    $add_stmt->bind_param('iis', $calories, $userId, $today_date);
    $add_stmt->execute();
}

function removeCalories($conn, $userId, $calories, $today_date) {
    $remove_stmt = $conn->prepare("UPDATE user_daily_calories SET total_calories = total_calories - ? WHERE user_id = ? AND log_date = ?");
    $remove_stmt->bind_param('iis', $calories, $userId, $today_date);
    $remove_stmt->execute();
}

function getScheduledCalories($conn, $userId, $today) {
    $scheduled_calories_query = "SELECT COALESCE(SUM(r.calories), 0) AS total
                FROM meal_schedule ms
                JOIN recipes r ON ms.recipe_id = r.recipe_id
                WHERE ms.user_id = ? AND ms.day_of_week = ?";
    $scheduled_calories_stmt = $conn->prepare($scheduled_calories_query);
    $scheduled_calories_stmt->bind_param('is', $userId, $today);
    $scheduled_calories_stmt->execute();
    return $scheduled_calories_stmt->get_result()->fetch_assoc();
}

function checkCalories($conn, $userId, $today_date, $scheduled_calories) {
    $check_today_row = $conn->prepare("SELECT log_date, total_calories FROM user_daily_calories WHERE user_id = ?");
    $check_today_row->bind_param('i', $userId);
    $check_today_row->execute();
    $existing_row = $check_today_row->get_result()->fetch_assoc();

    if (!$existing_row) {
        $insert_row = $conn->prepare("INSERT INTO user_daily_calories (user_id, log_date, total_calories) VALUES (?, ?, ?)");
        $insert_row->bind_param('isi', $userId, $today_date, $scheduled_calories['total']);
        $insert_row->execute();
    } elseif ($existing_row['log_date'] !== $today_date) {
        $reset_row = $conn->prepare("UPDATE user_daily_calories SET log_date = ?, total_calories = ? WHERE user_id = ?");
        $reset_row->bind_param('sii', $today_date, $scheduled_calories['total'], $userId);
        $reset_row->execute();
    }
}

function getTotalCalories($conn, $userId, $today_date) {
    $get_total_calories = $conn->prepare("SELECT total_calories FROM user_daily_calories WHERE user_id = ? AND log_date = ?");
    $get_total_calories->bind_param('is', $userId, $today_date);
    $get_total_calories->execute();
    return $get_total_calories->get_result()->fetch_assoc()['total_calories'];
}

function getDailyGoal($conn, $userId) {
    $goal_stmt = $conn->prepare("SELECT daily_goal FROM calorie_goals WHERE user_id = ?");
    $goal_stmt->bind_param('i', $userId);
    $goal_stmt->execute();
    $goal_row = $goal_stmt->get_result()->fetch_assoc();
    return $goal_row['daily_goal'] ?? 0;
}

?>