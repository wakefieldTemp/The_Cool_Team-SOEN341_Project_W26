<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];
// Tried to make it after logging out, you can't come back on this page (doesn't work D;)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Same error function used in the login page, shoutout Fred
//function showError($error){
//    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
//}

//Weekly schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $recipe_id  = intval($_POST['recipe_id']);
        $day        = $_POST['day_of_week'];
        $meal_type  = $_POST['meal_type'];

        // no duplicates in the same day for the same meal_type
        $check = $conn->prepare("SELECT schedule_id FROM meal_schedule WHERE user_id=? AND (recipe_id=? OR (day_of_week=? AND meal_type=?))");
        $check->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $ins = $conn->prepare("INSERT INTO meal_schedule (user_id, recipe_id, day_of_week, meal_type) VALUES (?,?,?,?)");
            $ins->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
            $ins->execute();
        }
		else{ 
			$_SESSION['duplicate_error'] = "You already have this recipe in your schedule.";
		}
    }

    if ($_POST['action'] === 'delete') { //destroy that thing
        $schedule_id = intval($_POST['schedule_id']);
        $del = $conn->prepare("DELETE FROM meal_schedule WHERE schedule_id=? AND user_id=?");
        $del->bind_param('ii', $schedule_id, $userId);
        $del->execute();
    }
    
    header("Location: main_menu.php");
    exit();
}

// Grab and clear the error, same pattern as index.php
$errors = ['schedule' => $_SESSION['schedule_error'] ?? ''];
unset($_SESSION['schedule_error']);


$meals_query = $conn->prepare(" 
    SELECT ms.schedule_id, ms.day_of_week, ms.meal_type, r.recipe_name
    FROM meal_schedule ms
    JOIN recipes r ON ms.recipe_id = r.recipe_id
    WHERE ms.user_id = ?
    ORDER BY FIELD(ms.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
             FIELD(ms.meal_type,'Breakfast','Lunch','Diner','Snack') ");
$meals_query->bind_param('i', $userId);
$meals_query->execute();
$meals_result = $meals_query->get_result(); //find every meal


//array of days (collumns) and meal_type (row) for the schedule table
$schedule = [];
$days      = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$meal_types = ['Breakfast','Lunch','Diner','Snack'];
foreach ($days as $d) $schedule[$d] = [];
while ($row = $meals_result->fetch_assoc()) {
    $schedule[$row['day_of_week']][] = $row;
}


// recipes in the cool "Add" dropdown menu
$user_recipes = $conn->prepare("SELECT recipe_id, recipe_name FROM recipes WHERE user_id=? ORDER BY recipe_name ASC");
$user_recipes->bind_param('i', $userId);
$user_recipes->execute();
$recipes_result = $user_recipes->get_result();
$user_recipe_list = [];
while ($r = $recipes_result->fetch_assoc()) $user_recipe_list[] = $r;


date_default_timezone_set('America/Toronto'); //Adjusted for our timezone
$today = date('l');
?>




<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Main Menu</title>
    <link rel="stylesheet" href="main_menu_style.css">
	<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
	<!-- The main menu has a side bar with the different pages we can go to and the profile page on the top right  -->
	<div class="sidebar">
		<div class="top">
			<div class="logo">
				<i class='bx bxs-dish' ></i>
				<span>MealMajor</span>
			</div>
			<i class="bx bx-menu" id="btn" ></i>
		</div>
		<ul>
			<li>
				<a href="recipes.php">
					<i class='bx bx-fork' ></i>
					<span class="links_name">Your recipes</span>
				</a>
				<span class="tooltip">Your recipes</span>
			</li>
			<li>
				<a href="calorie_tracker.php">
					<i class='bx bxs-heart'></i>
					<span class="links_name">Calorie Tracker</span>
				</a>
				<span class="tooltip">Calorie Tracker</span>
			</li>
			<li>
				<a href="recipe_creation.php">
					<i class='bx bxs-bowl-rice' ></i>
					<span class="links_name">Recipe Creator</span>
				</a>
				<span class="tooltip">Recipe Creator</span>
			</li>
			<li id="log-out">
				<a href="log_out.php">
					<i class='bx bx-log-out' ></i>
					<span class="links_name">Log Out</span>
				</a>
				<span class="tooltip">Log Out</span>
		</ul>
	</div>

	<div class="main-content">
    <div class="schedule-wrapper">
        <h2 class="schedule-title">Weekly Meal Schedule</h2>
		<?= showError($errors['schedule']) ?>
        <div class="week-grid">
            <?php foreach ($days as $day): ?>
            <div class="day-col <?= $day === $today ? 'today' : '' ?>">
                <div class="day-header">
                    <?= $day ?>
                    <?php if ($day === $today): ?><span class="today-badge">Today</span><?php endif; ?>
                </div>

                <?php foreach ($meal_types as $mt): ?>
                <div class="meal-slot">
                    <div class="meal-slot-label"><?= $mt ?></div>

                    <?php
                    
                    $slot_meals = array_filter($schedule[$day], fn($m) => $m['meal_type'] === $mt);
                    foreach ($slot_meals as $meal): ?>
                        <div class="meal-item">
                            <span><?= htmlspecialchars($meal['recipe_name']) ?></span>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action"      value="delete">
                                <input type="hidden" name="schedule_id" value="<?= $meal['schedule_id'] ?>">
                                <button type="submit" class="delete-meal-btn" title="Remove">✕</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
					
                    <?php if (empty($slot_meals)): ?>  <!-- Can only have one meal per slot -->
    					<button class="add-meal-btn" 
            				onclick="toggleAddForm('form-<?= $day ?>-<?= $mt ?>')">+ Add</button>

    					<div class="add-meal-form" id="form-<?= $day ?>-<?= $mt ?>" style="display:none;">
        					<form method="POST">
            					<input type="hidden" name="action"      value="add">
            					<input type="hidden" name="day_of_week" value="<?= $day ?>">
            					<input type="hidden" name="meal_type"   value="<?= $mt ?>">
            					<select name="recipe_id" required>
                					<option value="">-- Pick a recipe --</option>
                					<?php foreach ($user_recipe_list as $recipe): ?>
                					<option value="<?= $recipe['recipe_id'] ?>">
                    					<?= htmlspecialchars($recipe['recipe_name']) ?>
                					</option>
                					<?php endforeach; ?>
            						</select>
            						<button type="submit">Add</button>
            						<button type="button"
                    					onclick="toggleAddForm('form-<?= $day ?>-<?= $mt ?>')">Cancel</button>
        					</form>
    					</div>
					<?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
//automatically refreshes at midnight for the date to updates 
(function() {
    const now   = new Date();
    const msUntilMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1) - now;
    setTimeout(() => location.reload(), msUntilMidnight);
})();

function toggleAddForm(id) {
    const form = document.getElementById(id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>



	
	<a href="profile.php" class="profile-btn" title="Profile" aria-label="Profile">P</a>

</body>

<script>
	// Script for the cool sidebar popping out
	let btn = document.querySelector('#btn');
	let sidebar = document.querySelector('.sidebar');

	btn.onclick = function(){
		sidebar.classList.toggle('active');
	};
</script>

</html>

