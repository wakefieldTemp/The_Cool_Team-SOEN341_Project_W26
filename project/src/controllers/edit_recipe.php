<?php
/*This whole code is legit the same as the add recipes, just we load the values that we already have for the specific
  recipe (the placeholders becomes the current values) */
session_start();
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_recipe_functions.php';
$userId = $_SESSION['user_id'];

$recipe_id = $_GET['recipe_id'] ?? null;

if (!$recipe_id) {
    header('Location: ' . BASE_URL . '/src/views/recipes.php');
    exit;
}

// When the user clicks the save recipe button
if(isset($_POST['save_recipe'])) {
    $recipe_name        = trim($_POST['recipe_name']);
    $recipe_description = trim($_POST['recipe_description']);
    $recipe_ingredients = isset($_POST['ingredients']) ? json_decode($_POST['ingredients'], true) : [];
    $recipe_steps       = isset($_POST['steps']) ? json_decode($_POST['steps'], true) : [];
    $prep_time          = intval($_POST['prep_time']);
    $cook_time          = intval($_POST['cook_time']);
    $difficulty         = $_POST['difficulty'];
    $meal_type          = $_POST['meal_type'];
    $calories           = intval($_POST['calories']);
    $dietary_tags       = isset($_POST['dietary_tags']) ? $_POST['dietary_tags'] : [];
    $gmo_free           = in_array('gmo_free', $dietary_tags) ? 1 : 0;
    $gluten_free        = in_array('gluten_free', $dietary_tags) ? 1 : 0;
    $lactose_free       = in_array('lactose_free', $dietary_tags) ? 1 : 0;
    $vegan              = in_array('vegan', $dietary_tags) ? 1 : 0;
    $vegetarian         = in_array('vegetarian', $dietary_tags) ? 1 : 0;

    editRecipe($userId, $recipe_id, $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type, $recipe_ingredients, $recipe_steps);

    header('Location: ' . BASE_URL . '/src/views/recipes.php');
    exit;
}

// We fetch the information of the recipe (works the same as the display recipes)
$result = $conn->prepare("SELECT * FROM recipes WHERE recipe_id = ? AND user_id = ?");
$result->bind_param('ii', $recipe_id, $userId);
$result->execute();
$recipe = $result->get_result()->fetch_assoc();

if (!$recipe) {
    header('Location: ' . BASE_URL . '/src/views/recipes.php');
    exit;
}
$ingredient_result = $conn->prepare("SELECT i.ingredient_name FROM ingredients i JOIN recipe_ingredients ri ON i.ingredient_id = ri.ingredient_id WHERE ri.recipe_id = ?");
$ingredient_result->bind_param('i', $recipe_id);
$ingredient_result->execute();
$ingredient_result = $ingredient_result->get_result();
$existing_ingredients = [];
while ($row = $ingredient_result->fetch_assoc()) {
    $existing_ingredients[] = $row['ingredient_name'];
}
$step_result = $conn->prepare("SELECT step_instruction FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number");
$step_result->bind_param('i', $recipe_id);
$step_result->execute();
$step_result = $step_result->get_result();
$existing_steps = [];
while ($row = $step_result->fetch_assoc()) {
    $existing_steps[] = $row['step_instruction'];
}
?>

<!DOCTYPE html>
<!-- Like said at the beginning, instead of placeholders, we put in the value of the current recipe (loaded in the php section)  -->
<html lang="en">

    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Add Recipe</title>

      <link rel="stylesheet" href="<?= BASE_URL ?> /public/css/add_recipe_style.css" />
    </head>

<body>
  <div class="page">
    <div class="card">
      <h2>Edit Recipe</h2>

      <form id="main-form" method="POST" class="form">
        <input type="hidden" name="recipe_id" value="<?=$recipe_id?>">

        <div class="field">
          <label>Recipe Name</label>
          <input type="text" name="recipe_name" value="<?=htmlspecialchars($recipe['recipe_name'])?>" required>
        </div>

        <div class="field">
          <label>Recipe Description</label>
          <textarea name="recipe_description" required><?=htmlspecialchars($recipe['description'])?></textarea>
        </div>

        <div class="section">
          <h3>Ingredients</h3>

          <div class="row">
            <input type="text" id="ingredient_name" placeholder="Ingredient name">
            <button type="button" id="add_ingredient" class="btn btn-primary">Add Ingredient</button>
          </div>

          <div class="table-wrap">
            <table id="ingredients-list" class="table">
              <thead>
                <tr><th>Ingredient</th><th>Remove</th></tr>
              </thead>
              <tbody id="ingredients-tbody">
                <?php foreach($existing_ingredients as $ingredient): ?>
                  <tr>
                    <td><?=htmlspecialchars($ingredient)?></td>
                    <td>
                      <button type="button" class="btn btn-danger" onclick="removeIngredient(this)">Remove</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <input type="hidden" name="ingredients" id="ingredients_input">
        </div>

        <!-- Two-column grid for times + selects -->
        <div class="grid-2">
          <div class="field">
            <label>Prep Time (minutes)</label>
            <input type="number" min="0" name="prep_time" value="<?=$recipe['prep_time']?>" required>
          </div>

          <div class="field">
            <label>Cook Time (minutes)</label>
            <input type="number" min="0" name="cook_time" value="<?=$recipe['cook_time']?>" required>
          </div>

          <div class="field">
            <label>Difficulty</label>
            <select name="difficulty" required>
              <option value="">Select difficulty</option>
              <option value="easy"   <?=($recipe['difficulty_level'] == 'easy')   ? 'selected' : ''?>>Easy</option>
              <option value="medium" <?=($recipe['difficulty_level'] == 'medium') ? 'selected' : ''?>>Medium</option>
              <option value="hard"   <?=($recipe['difficulty_level'] == 'hard')   ? 'selected' : ''?>>Hard</option>
            </select>
          </div>

          <div class="field">
            <label>Meal Type</label>
            <select name="meal_type" required>
              <option value="">Select meal type</option>
              <option value="breakfast" <?=($recipe['meal_type'] == 'breakfast') ? 'selected' : ''?>>Breakfast</option>
              <option value="lunch"     <?=($recipe['meal_type'] == 'lunch')     ? 'selected' : ''?>>Lunch</option>
              <option value="dinner"    <?=($recipe['meal_type'] == 'dinner')    ? 'selected' : ''?>>Dinner</option>
            </select>
          </div>

          <div class="field">
            <label>Calories</label>
            <input type="number" min="0" name="calories" value="<?=$recipe['calories']?>" required>
          </div>

          <div class="field">
            <label>Dietary Tags</label>
            <div class="checkbox-group">
              <label><input type="checkbox" name="dietary_tags[]" value="gmo_free"     <?=$recipe['gmo_free']     ? 'checked' : ''?>> GMO-Free</label>
              <label><input type="checkbox" name="dietary_tags[]" value="gluten_free"  <?=$recipe['gluten_free']  ? 'checked' : ''?>> Gluten-Free</label>
              <label><input type="checkbox" name="dietary_tags[]" value="lactose_free" <?=$recipe['lactose_free'] ? 'checked' : ''?>> Lactose-Free</label>
              <label><input type="checkbox" name="dietary_tags[]" value="vegan"        <?=$recipe['vegan']        ? 'checked' : ''?>> Vegan</label>
              <label><input type="checkbox" name="dietary_tags[]" value="vegetarian"   <?=$recipe['vegetarian']   ? 'checked' : ''?>> Vegetarian</label>
            </div>
          </div>
        </div>

        <div class="section">
          <h3>Steps</h3>

          <div class="row">
            <textarea id="step_name" placeholder="Step description"></textarea>
            <button type="button" id="add_step" class="btn btn-primary">Add Step</button>
          </div>

          <div class="table-wrap">
            <table id="steps-list" class="table">
              <thead>
                <tr><th>Step</th><th>Remove</th></tr>
              </thead>
              <tbody id="steps-tbody">
                <?php foreach($existing_steps as $step): ?>
                  <tr>
                    <td><?=htmlspecialchars($step)?></td>
                    <td>
                      <button type="button" class="btn btn-danger" onclick="removeStep(this)">Remove</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <input type="hidden" name="steps" id="steps_input">
        </div>

        <!-- Save -->
        <button type="submit" name="save_recipe" class="btn btn-primary btn-block">
          Save Recipe
        </button>
      </form>
    </div>
  </div>


<script>
    // This section is the exact same as the add recipe
    function checkIngredient(ingredientName) {
        const rows = document.getElementById('ingredients-tbody').getElementsByTagName('tr');
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].cells[0].innerText === ingredientName) return true;
        }
        return false;
    }

    function removeIngredient(btn) { btn.closest('tr').remove(); }
    function removeStep(btn) { btn.closest('tr').remove(); }

    document.getElementById('add_ingredient').addEventListener('click', function () {
        const input = document.getElementById('ingredient_name');
        const name  = input.value.trim();
        if (name !== '' && !checkIngredient(name)) {
            const tbody = document.getElementById('ingredients-tbody');
            const row   = document.createElement('tr');
            row.innerHTML = `<td>${name}</td><td><button type="button" class="btn btn-danger" onclick="removeIngredient(this)">Remove</button></td>`;
            tbody.appendChild(row);
            input.value = '';
        }
    });

    document.getElementById('add_step').addEventListener('click', function () {
        const input = document.getElementById('step_name');
        const name  = input.value.trim();
        if (name !== '') {
            const tbody = document.getElementById('steps-tbody');
            const row   = document.createElement('tr');
            row.innerHTML = `<td>${name}</td><td><button type="button" class="btn btn-danger" onclick="removeStep(this)">Remove</button></td>`;
            tbody.appendChild(row);
            input.value = '';
        }
    });

    document.getElementById('main-form').addEventListener('submit', function () {
        const ingredientRows = document.getElementById('ingredients-tbody').getElementsByTagName('tr');
        document.getElementById('ingredients_input').value = JSON.stringify(Array.from(ingredientRows).map(r => r.cells[0].innerText));

        const stepRows = document.getElementById('steps-tbody').getElementsByTagName('tr');
        document.getElementById('steps_input').value = JSON.stringify(Array.from(stepRows).map(r => r.cells[0].innerText));
    });
</script>
</body>
</html>
