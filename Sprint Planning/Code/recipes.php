<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

$order_by = 'recipe_name';
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
  <title>My Recipes</title>

  <!-- use a dedicated CSS for this page -->
  <link rel="stylesheet" href="recipes_style.css">
</head>

<body>

<header class="site-header">
  <div class="brand">
    <img class="logo" src="logo.jpg" alt="Logo">
    <div class="title">The Cool Team App</div>
  </div>

  <div class="header-actions">
    <a class="btn btn-primary" href="add_recipe.php">+ Add Recipe</a>
    <button class="btn btn-primary" onclick="window.location.href='main_menu.php'">Back to Main Page</button>
  </div>
</header>

<main class="page">
  <section class="card">
    <h2>My Recipes</h2>

    <div class="recipes-container">
      <?php
      if ($recipes->num_rows > 0) {
          while ($row = $recipes->fetch_assoc()) {
              $recipe_id = $row['recipe_id'];

              // Ingredients
              $ingredients_query = "SELECT i.ingredient_name
                                   FROM ingredients i
                                   JOIN recipe_ingredients ri ON i.ingredient_id = ri.ingredient_id
                                   WHERE ri.recipe_id = ?";
              $ingredients_stmt = $conn->prepare($ingredients_query);
              $ingredients_stmt->bind_param('i', $recipe_id);
              $ingredients_stmt->execute();
              $ingredients_result = $ingredients_stmt->get_result();

              $ingredients = [];
              while ($ingredient = $ingredients_result->fetch_assoc()) {
                  $ingredients[] = htmlspecialchars($ingredient['ingredient_name']);
              }
              $ingredients_display = !empty($ingredients)
                  ? implode(', ', $ingredients)
                  : 'No ingredients listed.';

              $step_query = "SELECT step_number, step_instruction
                             FROM recipe_steps
                             WHERE recipe_id = ?
                             ORDER BY step_number ASC";
              $step_stmt = $conn->prepare($step_query);
              $step_stmt->bind_param('i', $recipe_id);
              $step_stmt->execute();
              $steps_result = $step_stmt->get_result();

              $steps_html = '';
              while ($step = $steps_result->fetch_assoc()) {
                  $steps_html .= '<div class="step-item"><strong>Step '
                      . htmlspecialchars($step['step_number'])
                      . ':</strong> '
                      . htmlspecialchars($step['step_instruction'])
                      . '</div>';
              }
              if ($steps_html === '') $steps_html = '<div class="muted">No steps listed.</div>';

              // Tags
              $tags = [];
              if ($row['gmo_free'])      $tags[] = 'GMO Free';
              if ($row['gluten_free'])   $tags[] = 'Gluten Free';
              if ($row['lactose_free'])  $tags[] = 'Lactose Free';
              if ($row['vegan'])         $tags[] = 'Vegan';
              if ($row['vegetarian'])    $tags[] = 'Vegetarian';
              $tags_display = !empty($tags) ? implode(' · ', $tags) : 'No dietary tags';

              echo '
              <article class="recipe-card">
                <button type="button" class="recipe-summary" onclick="toggleRecipe(this)">
                  <div class="left">
                    <h3 class="recipe-title">' . htmlspecialchars($row['recipe_name']) . '</h3>
                    <p class="recipe-description">' . htmlspecialchars($row['description']) . '</p>

                    <div class="meta">
                      <span>Prep: ' . (int)$row['prep_time'] . ' min</span>
                      <span>Cook: ' . (int)$row['cook_time'] . ' min</span>
                      <span>Difficulty: ' . htmlspecialchars($row['difficulty_level']) . '</span>
                      <span>Calories: ' . (int)$row['calories'] . '</span>
                      <span>Meal: ' . htmlspecialchars($row['meal_type']) . '</span>
                    </div>
                  </div>

                  <div class="chev" aria-hidden="true">▾</div>
                </button>

                <div class="recipe-details">
                  <div class="detail-block">
                    <div class="detail-title">Ingredients</div>
                    <div class="detail-body">' . $ingredients_display . '</div>
                  </div>

                  <div class="detail-block">
                    <div class="detail-title">Dietary Tags</div>
                    <div class="detail-body tags">' . $tags_display . '</div>
                  </div>

                  <div class="detail-block">
                    <div class="detail-title">Steps</div>
                    <div class="detail-body steps">' . $steps_html . '</div>
                  </div>
                </div>
              </article>';
          }
      } else {
          echo '<div class="empty-state">
                  <p>No recipes found.</p>
                  <a class="btn btn-primary" href="add_recipe.php">Add your first recipe</a>
                </div>';
      }
      ?>
    </div>
  </section>
</main>

<script>
  function toggleRecipe(btn){
    const card = btn.closest('.recipe-card');
    card.classList.toggle('open');
  }
</script>

</body>
</html>