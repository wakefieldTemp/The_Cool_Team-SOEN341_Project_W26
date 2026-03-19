<?php
require 'api_config.php';
require 'login_page_config.php';
session_start();
$userId = $_SESSION['user_id'];
$show_current_recipe = false;
$recipe = null;
$recipe_ingredients = [];
$meal_type = '';

if(isset($_POST['save_recipe'])) {
    $recipe = json_decode($_POST['recipe_data'], true);
    $meal_type = $_POST['meal_type'];
    $name         = $recipe['name'];
    $description  = $recipe['description'];
    $prep_time    = (int) $recipe['prep_time_minutes'];
    $cook_time    = (int) $recipe['cook_time_minutes'];
    $difficulty   = $recipe['difficulty'];
    $calories     = (int) $recipe['calories'];
    $gmo_free     = $recipe['gmo_free']     ? 1 : 0;
    $gluten_free  = $recipe['gluten_free']  ? 1 : 0;
    $lactose_free = $recipe['lactose_free'] ? 1 : 0;
    $vegan        = $recipe['vegan']        ? 1 : 0;
    $vegetarian   = $recipe['vegetarian']   ? 1 : 0;

    $recipe_insert_query = "INSERT INTO recipes (user_id, recipe_name, description, prep_time, cook_time, difficulty_level, calories, gmo_free, gluten_free, lactose_free, vegan, vegetarian, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $recipe_stmt = $conn->prepare($recipe_insert_query);
    $recipe_stmt->bind_param('issiisiiiiiis',
        $userId,
        $name,
        $description,
        $prep_time,
        $cook_time,
        $difficulty,
        $calories,
        $gmo_free,
        $gluten_free,
        $lactose_free,
        $vegan ,
        $vegetarian,
        $meal_type
    );

    $recipe_stmt->execute();
    $recipe_id = $conn->insert_id;

    foreach($recipe['ingredients'] as $ingredient) {
        $ingredient = trim($ingredient);

        $ingredient_query = "SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?";
        $ingredient_result = $conn->prepare($ingredient_query);
        $ingredient_result->bind_param('s', $ingredient);
        $ingredient_result->execute();
        $ingredient_result->store_result();

        if($ingredient_result->num_rows > 0) {
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

    foreach($recipe['steps'] as $index => $step) {
        $step = trim($step);
        $recipe_step_insert_query = "INSERT INTO recipe_steps (recipe_id, step_number, step_instruction) VALUES (?, ?, ?)";
        $recipe_step_result = $conn->prepare($recipe_step_insert_query);
        $step_number = $index + 1;
        $recipe_step_result->bind_param('iis', $recipe_id, $step_number, $step);
        $recipe_step_result->execute();
    }

    header("Location: main_menu.php");
    exit();
}

if(isset($_POST['create_recipe'])) {
    $recipe_ingredients = isset($_POST['ingredients']) ? json_decode($_POST['ingredients'], true) : [];
    if (!is_array($recipe_ingredients)) {
        $recipe_ingredients = [];
    }
    $recipe_ingredients_string = implode(", ", $recipe_ingredients);
    $meal_type = $_POST['meal_type'] ?? '';

    $sql_query = "
        SELECT al.allergy_id, al.allergy
        FROM user_allergies ual
        JOIN allergies al ON ual.allergy_id = al.allergy_id
        WHERE ual.user_id = ?
        ORDER BY al.allergy
    ";
    $stmt = $conn->prepare($sql_query);
    $stmt->bind_param('i', $userId);
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
    $stmt->bind_param('i', $userId);
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
    Meal type: $meal_type

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
    \"ingredients\": [\"ingredient1\", \"ingredient2\"],
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

    if (!isset($result['content'][0]['text'])) {
        die("<pre>API Error: " . htmlspecialchars($response) . "</pre>");
    }

    $recipeJson = $result['content'][0]['text'];
    $recipeJson = preg_replace('/^```(?:json)?\s*/i', '', trim($recipeJson));
    $recipeJson = preg_replace('/\s*```$/', '', $recipeJson);

    $recipe = json_decode($recipeJson, true);
    $show_current_recipe = ($recipe !== null);
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
                <input type="hidden" name="ingredients" id="ingredients_input">
            </section>

            <div class="field">
                <label for="meal_type">Meal Type</label>
                <select id="meal_type" name="meal_type" required>
                    <option value="">Select meal type</option>
                    <option value="breakfast">Breakfast</option>
                    <option value="lunch">Lunch</option>
                    <option value="dinner">Dinner</option>
                    <option value="snack">Snack</option>
                </select>
            </div>

            <button type="submit" name="create_recipe" class="btn btn-primary btn-block">
                Create Recipe
            </button>
        </div>
    </form>

    <div class="new-recipe">
        <?php if ($show_current_recipe): ?>
            <div class="recipe-card" id="recipe-result">

                <div class="recipe-header">
                    <h2><?= htmlspecialchars($recipe['name']) ?></h2>
                    <p><?= htmlspecialchars($recipe['description']) ?></p>
                </div>

                <div class="recipe-stats">
                    <div class="stat">
                        <span class="stat-label">Prep</span>
                        <span class="stat-value"><?= $recipe['prep_time_minutes'] ?> <span class="stat-unit">min</span></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Cook</span>
                        <span class="stat-value"><?= $recipe['cook_time_minutes'] ?> <span class="stat-unit">min</span></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Calories</span>
                        <span class="stat-value"><?= $recipe['calories'] ?> <span class="stat-unit">kcal</span></span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Difficulty</span>
                        <span class="badge-difficulty <?= htmlspecialchars($recipe['difficulty']) ?>">
                            <?= ucfirst(htmlspecialchars($recipe['difficulty'])) ?>
                        </span>
                    </div>
                </div>

                <div class="recipe-tags">
                    <div class="recipe-tags-label">Dietary info</div>
                    <div class="tags-list">
                        <?php
                        $dietaryFlags = [
                            'gmo_free'     => 'GMO Free',
                            'gluten_free'  => 'Gluten Free',
                            'lactose_free' => 'Lactose Free',
                            'vegan'        => 'Vegan',
                            'vegetarian'   => 'Vegetarian',
                        ];
                        foreach ($dietaryFlags as $key => $label):
                            $isYes = !empty($recipe[$key]);
                        ?>
                            <span class="tag <?= $isYes ? 'tag-yes' : 'tag-no' ?>">
                                <?= $label ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($recipe['ingredients'])): ?>
                <div class="recipe-ingredients">
                    <div class="recipe-section-label">Ingredients</div>
                    <ul class="ingredients-list">
                        <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                            <li><?= htmlspecialchars($ingredient) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="recipe-steps">
                    <div class="recipe-steps-label">Steps</div>
                    <ol class="steps-list">
                        <?php foreach ($recipe['steps'] as $i => $step): ?>
                            <li>
                                <span class="step-number"><?= $i + 1 ?></span>
                                <span class="step-text"><?= htmlspecialchars($step) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <div class="recipe-actions">
                    <form method="POST">
                        <input type="hidden" name="recipe_data" value="<?= htmlspecialchars(json_encode($recipe)) ?>">
                        <input type="hidden" name="meal_type" value="<?= htmlspecialchars($meal_type) ?>">
                        <button type="submit" name="save_recipe" class="btn btn-primary">Save to my recipes</button>
                    </form>
                    <button type="button" class="btn btn-ghost" id="btn-cancel">Cancel</button>
                </div>

            </div>
        <?php endif; ?>
    </div>

</body>
</html>

<script>
    function checkIngredient(ingredientName) {
        const tbody = document.getElementById('ingredients-tbody');
        const rows = tbody.getElementsByTagName('tr');
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].cells[0].innerText === ingredientName) {
                return true;
            }
        }
        return false;
    }

    function removeIngredient(btn) {
        btn.closest('tr').remove();
    }

    document.getElementById('add_ingredient').addEventListener('click', function () {
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

    document.getElementById('main-form').addEventListener('submit', function (event) {
        const ingredientRows = document.getElementById('ingredients-tbody').getElementsByTagName('tr');
        const ingredients = Array.from(ingredientRows).map(row => row.cells[0].innerText);
        document.getElementById('ingredients_input').value = JSON.stringify(ingredients);
    });

    const btnCancel = document.getElementById('btn-cancel');
    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            document.getElementById('recipe-result').style.display = 'none';
        });
    }
</script>
