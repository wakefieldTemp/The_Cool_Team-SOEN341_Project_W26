<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

if(isset($_POST['delete_recipe'])) {
    $recipe_id = $_POST['recipe_id'];
    $delete_query = "DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $recipe_id, $userId);
    $delete_stmt->execute();
}

$search_name = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? '';
$cook_time_filter = $_GET['cook_time_filter'] ?? '';
$prep_time_filter = $_GET['prep_time_filter'] ?? '';
switch($cook_time_filter) {
    case 'under_15':
        $cook_time_filter = 15;
        break;
    case 'under_30':
        $cook_time_filter = 30;
        break;
    case 'under_60':
        $cook_time_filter = 60;
        break;
    case 'over_60':
        $cook_time_filter = 1000;
        break;
    default:
        $cook_time_filter = 1000;
}

switch($prep_time_filter) {
    case 'under_15':
        $prep_time_filter = 15;
        break;
    case 'under_30':
        $prep_time_filter = 30;
        break;
    case 'under_60':
        $prep_time_filter = 60;
        break;
    case 'over_60':
        $prep_time_filter = 1000;
        break;
    default:
        $prep_time_filter = 1000;
}

switch($sort_by) {
    case 'name_desc':
        $order_by = 'recipe_name';
        $order_direction = 'DESC';
        break;
    case 'name_asc':
        $order_by = 'recipe_name';
        $order_direction = 'ASC';
        break;
    case 'cook_time_desc':
        $order_by = 'cook_time';
        $order_direction = 'DESC';
        break;
    case 'cook_time_asc':
        $order_by = 'cook_time';
        $order_direction = 'ASC';
        break;
    case 'prep_time_desc':
        $order_by = 'prep_time';
        $order_direction = 'DESC';
        break;
    case 'prep_time_asc':
        $order_by = 'prep_time';
        $order_direction = 'ASC';
        break;
    default:
        $order_by = 'recipe_name';
        $order_direction = 'ASC';
}

$sql_query = "SELECT recipe_id, recipe_name,
              description,
              prep_time,
              cook_time,
              difficulty_level,
              calories,
              gmo_free,
              gluten_free,
              lactose_free,
              vegan,
              vegetarian,
              meal_type FROM recipes WHERE user_id = ?
              AND prep_time <= $prep_time_filter 
              AND cook_time <= $cook_time_filter";

$filter_gmo_free     = isset($_GET['filter_gmo_free'])     ? 1 : null;
$filter_gluten_free  = isset($_GET['filter_gluten_free'])  ? 1 : null;
$filter_lactose_free = isset($_GET['filter_lactose_free']) ? 1 : null;
$filter_vegan        = isset($_GET['filter_vegan'])        ? 1 : null;
$filter_vegetarian   = isset($_GET['filter_vegetarian'])   ? 1 : null;

$filter_easy_diff   = isset($_GET['easy_diff'])   ? 1 : null;
$filter_medium_diff = isset($_GET['medium_diff']) ? 1 : null;
$filter_hard_diff   = isset($_GET['hard_diff'])   ? 1 : null;

if($filter_gmo_free)     $sql_query .= " AND gmo_free = 1";
if($filter_gluten_free)  $sql_query .= " AND gluten_free = 1";
if($filter_lactose_free) $sql_query .= " AND lactose_free = 1";
if($filter_vegan)        $sql_query .= " AND vegan = 1";
if($filter_vegetarian)   $sql_query .= " AND vegetarian = 1";
if($filter_easy_diff)    $sql_query .= " AND difficulty_level = 'Easy'";
if($filter_medium_diff)  $sql_query .= " AND difficulty_level = 'Medium'";
if($filter_hard_diff)    $sql_query .= " AND difficulty_level = 'Hard'";

$params = [$userId];
$types  = "i";

if ($search_name) {
    $sql_query .= " AND recipe_name LIKE ?";
    $params[]   = '%' . $search_name . '%';
    $types     .= "s";
}

$sql_query .= " ORDER BY $order_by $order_direction";

$result = $conn->prepare($sql_query);
$result->bind_param($types, ...$params);
$result->execute();
$recipes = $result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="profile_page_style.css">
</head>

<header class="site-header">
    <div class="brand">
        <img class="logo" src="logo.jpg" alt="Logo">

        <div class="title">The Cool Team App</div>
    </div>

    <div class="back-button-container">
        <button class="btn btn-primary" onclick="window.location.href='main_menu.php'">
            Back to Main Page
        </button>
    </div>
</header>

<body>
    <a href="add_recipe.php" class="add-recipe-btn" title="Add Recipes" aria-label="Add Recipes">Add Recipes</a>
    <div class="card">
        <h2>My Recipes</h2>
        <div class="recipes-container">
            <form method="get" action="">
            <div class="search-section">
                <h1>Search</h1>
                <input type="text" name="search" placeholder="Search recipes..." value="<?=htmlspecialchars($_GET['search'] ?? '')?>">
            </div>
            <div class="sort-section">
            <h1>Sort By</h1>
            <select name="sort" onchange="this.form.submit()">
                <option value="">Sort By</option>
                <option value="name_desc" <?=($sort_by == 'name_desc') ? 'selected' : ''?>>Name Descending</option>
                <option value="name_asc" <?=($sort_by == 'name_asc') ? 'selected' : ''?>>Name Ascending</option>
                <option value="cook_time_desc" <?=($sort_by == 'cook_time_desc') ? 'selected' : ''?>>Cook Time Descending</option>
                <option value="cook_time_asc" <?=($sort_by == 'cook_time_asc') ? 'selected' : ''?>>Cook Time Ascending</option>
                <option value="prep_time_desc" <?=($sort_by == 'prep_time_desc') ? 'selected' : ''?>>Prep Time Descending</option>
                <option value="prep_time_asc" <?=($sort_by == 'prep_time_asc') ? 'selected' : ''?>>Prep Time Ascending</option>
            </select>
            </div>
            <div class="filter-section">
            <h1>Filters</h1>
            <select name="prep_time_filter" onchange="this.form.submit()">
                <option value="">Prep Time</option>
                <option value="under_15" <?=($prep_time_filter == 'under_15') ? 'selected' : ''?>>Under 15 mins</option>
                <option value="under_30" <?=($prep_time_filter == 'under_30') ? 'selected' : ''?>>Under 30 mins</option>
                <option value="under_60" <?=($prep_time_filter == 'under_60') ? 'selected' : ''?>>Under 60 mins</option>
                <option value="over_60" <?=($prep_time_filter == 'over_60') ? 'selected' : ''?>>Over 60 mins</option>
            </select>
            <select name="cook_time_filter" onchange="this.form.submit()">
                <option value="">Cook Time</option>
                <option value="under_15" <?=($cook_time_filter == 'under_15') ? 'selected' : ''?>>Under 15 mins</option>
                <option value="under_30" <?=($cook_time_filter == 'under_30') ? 'selected' : ''?>>Under 30 mins</option>
                <option value="under_60" <?=($cook_time_filter == 'under_60') ? 'selected' : ''?>>Under 60 mins</option>
                <option value="over_60" <?=($cook_time_filter == 'over_60') ? 'selected' : ''?>>Over 60 mins</option>
            </select>
            <div class="tag-filters">
                <label><input type="checkbox" name="filter_gmo_free"     value="1" <?=isset($_GET['filter_gmo_free'])     ? 'checked' : ''?> onchange="this.form.submit()"> GMO Free</label>
                <label><input type="checkbox" name="filter_gluten_free"  value="1" <?=isset($_GET['filter_gluten_free'])  ? 'checked' : ''?> onchange="this.form.submit()"> Gluten Free</label>
                <label><input type="checkbox" name="filter_lactose_free" value="1" <?=isset($_GET['filter_lactose_free']) ? 'checked' : ''?> onchange="this.form.submit()"> Lactose Free</label>
                <label><input type="checkbox" name="filter_vegan"        value="1" <?=isset($_GET['filter_vegan'])        ? 'checked' : ''?> onchange="this.form.submit()"> Vegan</label>
                <label><input type="checkbox" name="filter_vegetarian"   value="1" <?=isset($_GET['filter_vegetarian'])   ? 'checked' : ''?> onchange="this.form.submit()"> Vegetarian</label>
            </div>
            <div class="diff-filters">
                <label><input type="checkbox" name="easy_diff"     value="1" <?=isset($_GET['easy_diff'])     ? 'checked' : ''?> onchange="this.form.submit()"> Easy Difficulty</label>
                <label><input type="checkbox" name="medium_diff"  value="1" <?=isset($_GET['medium_diff'])  ? 'checked' : ''?> onchange="this.form.submit()"> Medium Difficulty</label>
                <label><input type="checkbox" name="hard_diff" value="1" <?=isset($_GET['hard_diff']) ? 'checked' : ''?> onchange="this.form.submit()"> Hard Difficulty</label>     
            </div>
            </div>
            </form>
            <?php
if ($recipes->num_rows > 0) {
    while ($row = $recipes->fetch_assoc()) {
        $recipe_id = $row['recipe_id'];
        $ingredients_query = "SELECT i.ingredient_name 
                             FROM ingredients i
                             JOIN recipe_ingredients ri ON i.ingredient_id = ri.ingredient_id
                             WHERE ri.recipe_id = ?";
        $ingredients_stmt = $conn->prepare($ingredients_query);
        $ingredients_stmt->bind_param('i', $recipe_id);
        $ingredients_stmt->execute();
        $ingredients_result = $ingredients_stmt->get_result();
        $ingredients_display = 'Ingredients: ';
        $ingredients = [];
        while ($ingredient = $ingredients_result->fetch_assoc()) {
            $ingredients[] = htmlspecialchars($ingredient['ingredient_name']);
        }
        $ingredients_display = !empty($ingredients) ? implode(', ', $ingredients) : 'No ingredients listed.';

        $step_query = "SELECT step_number, step_instruction
                       FROM recipe_steps
                       WHERE recipe_id = ?";
        $step_stmt = $conn->prepare($step_query);
        $step_stmt->bind_param('i', $recipe_id);
        $step_stmt->execute();
        $steps_result = $step_stmt->get_result();
        $steps_display = '';
        while ($step = $steps_result->fetch_assoc()) {
            $steps_display .= '<p><strong>&nbsp Step ' . htmlspecialchars($step['step_number']) . ':</strong> ' . htmlspecialchars($step['step_instruction']) . '</p>';
        }

        $tags = [];
        if ($row['gmo_free'])      $tags[] = '<span>GMO Free &#9989</span>';
        if ($row['gluten_free'])   $tags[] = '<span>Gluten Free &#9989</span>';
        if ($row['lactose_free'])  $tags[] = '<span>Lactose Free &#9989</span>';
        if ($row['vegan'])         $tags[] = '<span>Vegan &#9989</span>';
        if ($row['vegetarian'])    $tags[] = '<span>Vegetarian &#9989</span>';
        $tags_display = !empty($tags) ? implode(' | ', $tags) : '<span>No dietary tags &#10060</span>';

        echo '
        <div class="recipe-card" onclick="toggleRecipe(this)">
            <div class="recipe-card-summary">
                    <h3 class="recipe-title">' . htmlspecialchars($row['recipe_name']) . '</h3>
                    <p class="recipe-description">' . htmlspecialchars($row['description']) . '</p>
                    <div class="recipe-info2">
                        <span>Prep time: ' . htmlspecialchars($row['prep_time']) . ' min | </span>
                        <span>Cook time: ' . htmlspecialchars($row['cook_time']) . ' min | </span>
                        <span>Difficulty: ' . htmlspecialchars($row['difficulty_level']) . ' | </span>
                        <span>Calories: ' . htmlspecialchars($row['calories']) . ' cal | </span>
                        <span>Meal Type: ' . htmlspecialchars($row['meal_type']) . '</span>                     
                    </div>
                    <div class="recipe-ingredients">' . $ingredients_display . '</div>
                    <div class="recipe-tags">' . $tags_display . '</div>
                    <div class="recipe-steps">' . $steps_display . '</div>
            </div>

            <div class="recipe-actions">
                <a href="edit_recipe.php?recipe_id=' . $recipe_id . '">Edit</a>
                <form method="POST">
                    <input type="hidden" name="recipe_id" value="' . $recipe_id . '">
                    <button type="submit" name="delete_recipe" onclick="return confirm(\'Are you sure you want to delete this recipe?\')">Delete</button>
                </form>
                <hr>
            </div>

            <div class>
                <hr>
            </div>
        </div>';
    }
} else {
    echo '<p>No recipes found. Click "Add Recipes" to create your first recipe!</p>';
}
?>
    </div>
</body>
</html>
