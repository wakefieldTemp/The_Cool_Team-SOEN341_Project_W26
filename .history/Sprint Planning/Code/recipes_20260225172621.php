<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

$order_by = 'recipe_name'; // THESE TWO VARIABLES WILL BE USED FOR SORTING
$order_direction = 'ASC';

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
              ORDER BY $order_by $order_direction";
$stmt = $conn->prepare($sql_query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$recipes = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
    <link rel="stylesheet" href="profile_page_style.css">
</head>

<body>
    <a href="add_recipe.php" class="add-recipe-btn" title="Add Recipes" aria-label="Add Recipes">Add Recipes</a>
    <div class="card">
        <h2>My Recipes</h2>
        <div class="recipes-container">
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