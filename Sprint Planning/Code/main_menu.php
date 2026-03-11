<?php
session_start();
require_once 'login_page_config.php';
// Tried to make it after logging out, you can't come back on this page (doesn't work D;)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION["breakfast_recipes"]) || isset($_POST["reset_recipes"])) {

    $recipe_breakfast_query = "
        SELECT recipe_id 
        FROM recipes 
        WHERE user_id = ? AND meal_type = 'breakfast'
        ORDER BY RAND()
        LIMIT 7
    ";

    $recipe_breakfast_stmt = $conn->prepare($recipe_breakfast_query);
    $recipe_breakfast_stmt->bind_param("i", $_SESSION['user_id']);
    $recipe_breakfast_stmt->execute();
    $recipe_breakfast_result = $recipe_breakfast_stmt->get_result();

    $_SESSION["breakfast_recipes"] = [];

    while ($row = $recipe_breakfast_result->fetch_assoc()) {
        $_SESSION["breakfast_recipes"][] = $row["recipe_id"];
    }
}
$breakfast_recipes = $_SESSION["breakfast_recipes"];

if (!isset($_SESSION["lunch_recipes"]) || isset($_POST["reset_recipes"])) {

    $recipe_breakfast_query = "
        SELECT recipe_id 
        FROM recipes 
        WHERE user_id = ? AND meal_type = 'lunch'
        ORDER BY RAND()
        LIMIT 7
    ";

    $recipe_lunch_stmt = $conn->prepare($recipe_lunch_query);
    $recipe_lunch_stmt->bind_param("i", $_SESSION['user_id']);
    $recipe_lunch_stmt->execute();
    $recipe_lunch_result = $recipe_lunch_stmt->get_result();

    $_SESSION["lunch_recipes"] = [];

    while ($row = $recipe_lunch_result->fetch_assoc()) {
        $_SESSION["lunch_recipes"][] = $row["recipe_id"];
    }
}
$lunch_recipes = $_SESSION["lunch_recipes"];

if (!isset($_SESSION["dinner_recipes"]) || isset($_POST["reset_recipes"])) {

    $recipe_dinner_query = "
        SELECT recipe_id 
        FROM recipes 
        WHERE user_id = ? AND meal_type = 'dinner'
        ORDER BY RAND()
        LIMIT 7
    ";

    $recipe_dinner_stmt = $conn->prepare($recipe_dinner_query);
    $recipe_dinner_stmt->bind_param("i", $_SESSION['user_id']);
    $recipe_dinner_stmt->execute();
    $recipe_dinner_result = $recipe_dinner_stmt->get_result();

    $_SESSION["dinner_recipes"] = [];

    while ($row = $recipe_dinner_result->fetch_assoc()) {
        $_SESSION["dinner_recipes"][] = $row["recipe_id"];
    }
}
$dinner_recipes = $_SESSION["dinner_recipes"];

if (!isset($_SESSION["snack_recipes"]) || isset($_POST["reset_recipes"])) {

    $recipe_snack_query = "
        SELECT recipe_id 
        FROM recipes 
        WHERE user_id = ? AND meal_type = 'snack'
        ORDER BY RAND()
        LIMIT 7
    ";

    $recipe_snack_stmt = $conn->prepare($recipe_snack_query);
    $recipe_snack_stmt->bind_param("i", $_SESSION['user_id']);
    $recipe_snack_stmt->execute();
    $recipe_snack_result = $recipe_snack_stmt->get_result();

    $_SESSION["snack_recipes"] = [];

    while ($row = $recipe_snack_result->fetch_assoc()) {
        $_SESSION["snack_recipes"][] = $row["recipe_id"];
    }
}
$snack_recipes = $_SESSION["snack_recipes"];

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
		<!-- This is where we're gonna add the schedule, right now its just a place holder  -->
		<div class="container">
			<h>Schedule</h>
		</div>
	</div>

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

