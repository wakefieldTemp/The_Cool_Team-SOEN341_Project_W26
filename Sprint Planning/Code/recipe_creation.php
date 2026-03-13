<?php
require 'api_config.php';
require 'login_page_config.php';
session_start();
$userId = $_SESSION['user_id'];
if(isset($_POST['create_recipe'])) {
    $recipe_ingredients = isset($_POST['ingredients']) ? json_decode($_POST['ingredients'], true) : [];
    if (!is_array($recipe_ingredients)) {
        $recipe_ingredients = [];
    }
    $recipe_ingredients_string = implode(", ", $recipe_ingredients);

    $sql_query = "
        SELECT al.allergy_id, al.allergy
        FROM user_allergies ual
        JOIN allergies al ON ual.allergy_id = al.allergy_id
        WHERE ual.user_id = ?
        ORDER BY al.allergy
    ";

    $stmt = $conn->prepare($sql_query);
    $stmt->bind_param('i', $userId);  // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result();
    $allergies = $result->fetch_all(MYSQLI_ASSOC);
    $allergiesList = array_column($allergies, 'allergy');
    $allergiesString = implode(", ", $allergiesList);


    $sql_query = "
        SELECT dp.preference_id, dp.preference
        FROM user_preferences udp
        JOIN diet_preferences dp ON udp.preference_id = dp.preference_id
        WHERE udp.user_id = ?
        ORDER BY dp.preference
    ";

    $stmt = $conn->prepare($sql_query);
    $stmt->bind_param('i', $userId);  // 'i' for integer
    $stmt->execute();
    $result = $stmt->get_result();
    $preferences = $result->fetch_all(MYSQLI_ASSOC);
    $preferencesList = array_column($preferences, 'preference');
    $preferencesString = implode(", ", $preferencesList);

    $prompt = "
    Create a recipe using only the provided ingredients.

    Ingredients: $recipe_ingredients_string
    Dietary preferences: $preferencesString
    Allergies: $allergiesString

    Return ONLY valid JSON with this exact structure:

    {
    \"name\": \"\",
    \"description\": \"\",
    \"prep_time_minutes\": number,
    \"cook_time_minutes\": number,
    \"difficulty\": \"easy|medium|hard\",
    \"calories\": number,
    \"gmo_free\": boolean,
    \"gluten_free\": boolean,
    \"lactose_free\": boolean,
    \"vegan\": boolean,
    \"vegetarian\": boolean,
    \"meal_type\": \"breakfast|lunch|dinner|snack\",
    \"steps\": [\"step1\",\"step2\",\"step3\"]
    }

    Rules:
    - Use only the ingredients listed.
    - Respect dietary preferences and allergies.
    - Output JSON only.
    ";

    $data = [
        "model" => "claude-sonnet-4-6",
        "max_tokens" => 1000,
        "messages" => [
            [
                "role" => "user",
                "content" => $prompt
            ]
        ]
    ];

    $ch = curl_init("https://api.anthropic.com/v1/messages");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: " . $ANTHROPIC_API_KEY,
        "anthropic-version: 2023-06-01"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if ($response === false) {
        die("cURL error: " . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);

    echo "<pre>";
    print_r($result);
    echo "</pre>";
}


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
    <div class="introduction">
        <h1>Welcome to your meal planner!</h1>
        <p>Here you can add ingredients that you own, and the system will help you create delicious recipes.</p>
    </div>
<form id="main-form" method="POST" class="form">
    <div class="create-recipe">
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
        <!-- When we add an ingredient, we're gonna add it into the table, but also here, because the php won't
             be able to take from the table, so we're kind of saving it into a "list", where we're putting it in
             JSON format for the php -->
        <input type="hidden" name="ingredients" id="ingredients_input">
      </section>
      <button type="submit" name="create_recipe" class="btn btn-primary btn-block">
        Create Recipe
      </button>
    </div>
</form>
</body>
</html>

<script>
        // This script is to check that the ingredient is not already added (We don't want the same ingredient twice in our recipe)
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

    // This is to remove the ingredient
    function removeIngredient(btn){
        btn.closest('tr').remove();
    }

    // When the add_ingredient button is clicked, we call it and we add the ingredient to the table and the list
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

    document.getElementById('main-form').addEventListener('submit', function(event) {
        const ingredientRows = document.getElementById('ingredients-tbody').getElementsByTagName('tr');
        const ingredients = Array.from(ingredientRows).map(row => row.cells[0].innerText);
        document.getElementById('ingredients_input').value = JSON.stringify(ingredients);

        const stepRows = document.getElementById('steps-tbody').getElementsByTagName('tr');
        const steps = Array.from(stepRows).map(row => row.cells[0].innerText);
        document.getElementById('steps_input').value = JSON.stringify(steps);
    });
</script>