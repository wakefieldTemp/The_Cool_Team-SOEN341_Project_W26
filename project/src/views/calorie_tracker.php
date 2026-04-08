<?php include __DIR__ . '/../controllers/calorie_tracker_post.php'; ?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calorie Tracker</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/calorie_tracker_style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="introduction">
        <h1>Welcome to your calorie tracker!</h1>
        <p>Here you can track your daily calorie intake depending on today's meal plan.</p>
        <p>You can add extra calories you consume, or remove calories</p>
    </div>
    <div class="back-button">
        <a href="<?= BASE_URL ?>/src/views/main_menu.php">
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