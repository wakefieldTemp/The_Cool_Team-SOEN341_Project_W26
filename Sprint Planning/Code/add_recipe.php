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
    $recipe_stmt->bind_param('issiisiiiiiiis', $userId, $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type);
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

<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Add Recipe</title>
    <link rel="stylesheet" href="add_recipe_style.css">
	<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

<div class="main">
    <form id="main-form" method="POST">

        <div>
            <h1>Recipe Name</h1>
            <input type="text" name="recipe_name" placeholder="Enter recipe name" required>
        </div>

        <div>
            <h1>Recipe Description</h1>
            <textarea name="recipe_description" placeholder="Enter recipe description" required></textarea>
        </div>

        <div>
            <h1>Ingredients</h1>
            <div id="ingredients-div">
                <input type="text" id="ingredient_name" placeholder="Ingredient name">
                <button type="button" id="add_ingredient" class="btn btn-primary">Add Ingredient</button>
            </div>
            <table id="ingredients-list">
                <thead>
                    <tr>
                        <th>Ingredient</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody id="ingredients-tbody">
                </tbody>
            </table>
            <input type="hidden" name="ingredients" id="ingredients_input">
        </div>

        <div>
            <h1>Prep Time</h1>
            <input type="number" min="0" name="prep_time" placeholder="Enter prep time in minutes" required>
        </div>

        <div>
            <h1>Cook Time</h1>
            <input type="number" min="0" name="cook_time" placeholder="Enter cook time in minutes" required>
        </div>

        <div>
            <h1>Difficulty</h1>
            <select name="difficulty" required>
                <option value="">Select difficulty</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
        </div>

        <div>
            <h1>Meal Type</h1>
            <select name="meal_type" required>
                <option value="">Select meal type</option>
                <option value="breakfast">Breakfast</option>
                <option value="lunch">Lunch</option>
                <option value="dinner">Dinner</option>
            </select>
        </div>
        
        <div>
            <h1>Calories</h1>
            <input type="number" min="0" name="calories" placeholder="Enter calories" required>
        </div>

        <div>
            <h1>Dietary Tags</h1>
            <label for="gmo_free">
                <input type="checkbox" name="dietary_tags[]" value="gmo_free" id="gmo_free"> GMO-Free
            </label>
            <label for="gluten_free">
                <input type="checkbox" name="dietary_tags[]" value="gluten_free" id="gluten_free"> Gluten-Free
            </label>
            <label for="lactose_free">
                <input type="checkbox" name="dietary_tags[]" value="lactose_free" id="lactose_free"> Lactose-Free
            </label>
            <label for="vegan">
                <input type="checkbox" name="dietary_tags[]" value="vegan" id="vegan"> Vegan
            </label>
            <label for="vegetarian">
                <input type="checkbox" name="dietary_tags[]" value="vegetarian" id="vegetarian"> Vegetarian
            </label>
        </div>

        <div>
            <h1>Steps</h1>
            <div id="steps-div">
                <textarea type="text" id="step_name" placeholder="Step description"></textarea>
                <button type="button" id="add_step" class="btn btn-primary">Add Step</button>
            </div>
            <table id="steps-list">
                <thead>
                    <tr>
                        <th>Step</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody id="steps-tbody">
                </tbody>
            </table>
            <input type="hidden" name="steps" id="steps_input">
        </div>
        <button type="submit" name="save_recipe" class="btn btn-success">Save Recipe</button>

    </form>
</div> 

</body>
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