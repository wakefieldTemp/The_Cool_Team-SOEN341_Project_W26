<?php
session_start();
require_once 'login_page_config.php';
$userId = $_SESSION['user_id'];

if(isset($_POST['save_recipe'])) {
    $recipe_name = trim($_POST['recipe_name']);
    $recipe_description = trim($_POST['recipe_description']);

    $recipe_ingredients = isset($_POST['ingredients']) ? json_decode($_POST['ingredients'], true) : [];
    $recipe_steps = isset($_POST['steps']) ? json_decode($_POST['steps'], true) : [];

    $prep_time = intval($_POST['prep_time']);
    $cook_time = intval($_POST['cook_time']);

    $difficulty = $_POST['difficulty'];

    $meal_type = $_POST['meal_type'];

    $calories = intval($_POST['calories']);

    $dietary_tags = isset($_POST['dietary_tags']) ? $_POST['dietary_tags'] : [];
    $gmo_free = in_array('gmo_free', $dietary_tags) ? 1 : 0;
    $gluten_free = in_array('gluten_free', $dietary_tags) ? 1 : 0;
    $lactose_free = in_array('lactose_free', $dietary_tags) ? 1 : 0;
    $vegan = in_array('vegan', $dietary_tags) ? 1 : 0;
    $vegetarian = in_array('vegetarian', $dietary_tags) ? 1 : 0;

    $recipe_insert_query = "INSERT INTO recipes (user_id, recipe_name, description, prep_time, cook_time, difficulty_level, calories, gmo_free, gluten_free, lactose_free, vegan, vegetarian, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $recipe_stmt = $conn->prepare($recipe_insert_query);
    $recipe_stmt->bind_param('issiisiiiiiis', $userId, $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type);
    $recipe_stmt->execute();
    $recipe_id = $conn->insert_id;

    // Lets check if the ingridients already exist
    foreach($recipe_ingredients as $ingredient){
        $ingredient = trim($ingredient);

        $ingredient_query = "SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?";
        $ingredient_result = $conn->prepare($ingredient_query);
        $ingredient_result->bind_param('s', $ingredient);
        $ingredient_result->execute();
        $ingredient_result->store_result();

        if($ingredient_result->num_rows > 0){
            $ingredient_result->bind_result($ingredient_id);
            $ingredient_result->fetch();
        } else {
            $insert_query = "INSERT INTO ingredients (ingredient_name) VALUES (?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('s', $ingredient);
            $insert_stmt->execute();
            $ingredient_id = $conn->insert_id;
        }
        $ingredient_result->close();

        $recipe_ingredient_insert_query = "INSERT INTO recipe_ingredients (recipe_id, ingredient_id) VALUES (?, ?)";
        $recipe_ingredient_result = $conn->prepare($recipe_ingredient_insert_query);
        $recipe_ingredient_result->bind_param('ii', $recipe_id, $ingredient_id);
        $recipe_ingredient_result->execute();
    }

    foreach($recipe_steps as $index => $step){
        $step = trim($step);
        $recipe_step_insert_query = "INSERT INTO recipe_steps (recipe_id, step_number, step_instruction) VALUES (?, ?, ?)";
        $recipe_step_result = $conn->prepare($recipe_step_insert_query);
        $step_number = $index + 1;
        $recipe_step_result->bind_param('iis', $recipe_id, $step_number, $step);
        $recipe_step_result->execute();
    }
    header('Location: recipes.php');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Add Recipe</title>

  <link rel="stylesheet" href="add_recipe_style.css" />
</head>

<body>

<header class="site-header">
  <div class="brand">
    <img class="logo" src="logo.jpg" alt="Logo">
    <div class="title">The Cool Team App</div>
  </div>

  <div class="back-button-container">
    <button class="btn btn-primary" onclick="window.location.href='recipes.php'">
      Back to Recipes
    </button>
  </div>
</header>

<main class="page">
  <section class="card">

    <h2>Add Recipe</h2>

    <form id="main-form" method="POST" class="form">

      <div class="field">
        <label for="recipe_name">Recipe Name</label>
        <input id="recipe_name" type="text" name="recipe_name" placeholder="Enter recipe name" required>
      </div>

      <div class="field">
        <label for="recipe_description">Recipe Description</label>
        <textarea id="recipe_description" name="recipe_description" placeholder="Enter recipe description" required></textarea>
      </div>

      <section class="section">
        <h3>Ingredients</h3>

        <div class="row">
          <input type="text" id="ingredient_name" placeholder="Ingredient name">
          <button type="button" id="add_ingredient" class="btn btn-primary">Add Ingredient</button>
        </div>

        <div class="table-wrap">
          <table id="ingredients-list" class="table">
            <thead>
              <tr>
                <th>Ingredient</th>
                <th style="width: 140px;">Remove</th>
              </tr>
            </thead>
            <tbody id="ingredients-tbody"></tbody>
          </table>
        </div>

        <input type="hidden" name="ingredients" id="ingredients_input">
      </section>

      <!-- QUICK INFO -->
      <div class="grid-2">
        <div class="field">
          <label for="prep_time">Prep Time (minutes)</label>
          <input id="prep_time" type="number" min="0" name="prep_time" placeholder="e.g., 15" required>
        </div>

        <div class="field">
          <label for="cook_time">Cook Time (minutes)</label>
          <input id="cook_time" type="number" min="0" name="cook_time" placeholder="e.g., 30" required>
        </div>

        <div class="field">
          <label for="difficulty">Difficulty</label>
          <select id="difficulty" name="difficulty" required>
            <option value="">Select difficulty</option>
            <option value="easy">Easy</option>
            <option value="medium">Medium</option>
            <option value="hard">Hard</option>
          </select>
        </div>

        <div class="field">
          <label for="meal_type">Meal Type</label>
          <select id="meal_type" name="meal_type" required>
            <option value="">Select meal type</option>
            <option value="breakfast">Breakfast</option>
            <option value="lunch">Lunch</option>
            <option value="dinner">Dinner</option>
          </select>
        </div>

        <div class="field">
          <label for="calories">Calories</label>
          <input id="calories" type="number" min="0" name="calories" placeholder="e.g., 450" required>
        </div>

        <div class="field">
          <label>Dietary Tags</label>
          <div class="checkbox-group">
            <label><input type="checkbox" name="dietary_tags[]" value="gmo_free"> GMO-Free</label>
            <label><input type="checkbox" name="dietary_tags[]" value="gluten_free"> Gluten-Free</label>
            <label><input type="checkbox" name="dietary_tags[]" value="lactose_free"> Lactose-Free</label>
            <label><input type="checkbox" name="dietary_tags[]" value="vegan"> Vegan</label>
            <label><input type="checkbox" name="dietary_tags[]" value="vegetarian"> Vegetarian</label>
          </div>
        </div>
      </div>

      <section class="section">
        <h3>Steps</h3>

        <div class="row">
          <textarea id="step_name" placeholder="Step description"></textarea>
          <button type="button" id="add_step" class="btn btn-primary">Add Step</button>
        </div>

        <div class="table-wrap">
          <table id="steps-list" class="table">
            <thead>
              <tr>
                <th>Step</th>
                <th style="width: 140px;">Remove</th>
              </tr>
            </thead>
            <tbody id="steps-tbody"></tbody>
          </table>
        </div>

        <input type="hidden" name="steps" id="steps_input">
      </section>

      <button type="submit" name="save_recipe" class="btn btn-primary btn-block">
        Save Recipe
      </button>

    </form>

  </section>
</main>

</body>
</html>

<script>

    function checkIngredient(ingredientName){
        const tbody = document.getElementById('ingredients-tbody');
        const rows = tbody.getElementsByTagName('tr');
        for(let i = 0; i < rows.length; i++){
            if(rows[i].cells[0].innerText === ingredientName){
                return true;
            }
        }        
        return false;
    }

    function removeIngredient(btn){
        btn.closest('tr').remove();
    }

    document.getElementById('add_ingredient').addEventListener('click', function() {
        const ingredientInput = document.getElementById('ingredient_name');
        const ingredientName = ingredientInput.value.trim();
        if (ingredientName !== '' && !checkIngredient(ingredientName)) {
            const tbody = document.getElementById('ingredients-tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${ingredientName}</td>
                <td><button type="button" class="btn btn-danger remove-ingredient" onclick="removeIngredient(this)">Remove</button></td>
            `;
            tbody.appendChild(row);
            ingredientInput.value = '';
        }
    });

    document.getElementById('add_step').addEventListener('click', function() {
        const stepInput = document.getElementById('step_name');
        const stepName = stepInput.value.trim();
        if (stepName !== '') {
            const tbody = document.getElementById('steps-tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${stepName}</td>
                <td><button type="button" class="btn btn-danger remove-step" onclick="removeStep(this)">Remove</button></td>
            `;
            tbody.appendChild(row);
            stepInput.value = '';
        }
    });

    function removeStep(btn){
        btn.closest('tr').remove();
    }

    document.getElementById('main-form').addEventListener('submit', function(event) {
        const ingredientRows = document.getElementById('ingredients-tbody').getElementsByTagName('tr');
        const ingredients = Array.from(ingredientRows).map(row => row.cells[0].innerText);
        document.getElementById('ingredients_input').value = JSON.stringify(ingredients);

        const stepRows = document.getElementById('steps-tbody').getElementsByTagName('tr');
        const steps = Array.from(stepRows).map(row => row.cells[0].innerText);
        document.getElementById('steps_input').value = JSON.stringify(steps);
    });

</script>
</html>