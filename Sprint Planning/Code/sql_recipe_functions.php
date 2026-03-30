<?php
require 'api_config.php';
require 'login_page_config.php';
function addRecipe($userId, $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type, $recipe_ingredients, $recipe_steps){
    global $conn;
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

        // If the ingredient already exists, then we don't need to add it to the ingredient table
        if($ingredient_result->num_rows > 0){
            $ingredient_result->bind_result($ingredient_id);
            $ingredient_result->fetch();
        } else { // If it doesn't, then we add it to the ingredient table
            $insert_query = "INSERT INTO ingredients (ingredient_name) VALUES (?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param('s', $ingredient);
            $insert_stmt->execute();
            $ingredient_id = $conn->insert_id;
        }
        $ingredient_result->close();

        // Then we add the ingredient to the recipe_ingredients (with its associated recipe id)
        $recipe_ingredient_insert_query = "INSERT INTO recipe_ingredients (recipe_id, ingredient_id) VALUES (?, ?)";
        $recipe_ingredient_result = $conn->prepare($recipe_ingredient_insert_query);
        $recipe_ingredient_result->bind_param('ii', $recipe_id, $ingredient_id);
        $recipe_ingredient_result->execute();
    }

    // Then we add te steps
    foreach($recipe_steps as $index => $step){
        $step = trim($step);
        $recipe_step_insert_query = "INSERT INTO recipe_steps (recipe_id, step_number, step_instruction) VALUES (?, ?, ?)";
        $recipe_step_result = $conn->prepare($recipe_step_insert_query);
        $step_number = $index + 1;
        $recipe_step_result->bind_param('iis', $recipe_id, $step_number, $step);
        $recipe_step_result->execute();
    }
}

function editRecipe($userID, $recipe_id, $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type, $recipe_ingredients, $recipe_steps){
    global $conn;
    $update_query = "UPDATE recipes SET recipe_name=?, description=?, prep_time=?, cook_time=?, difficulty_level=?, calories=?, gmo_free=?, gluten_free=?, lactose_free=?, vegan=?, vegetarian=?, meal_type=? WHERE recipe_id=? AND user_id=?";
    $result = $conn->prepare($update_query);
    $result->bind_param('ssiisiiiiiisii', $recipe_name, $recipe_description, $prep_time, $cook_time, $difficulty, $calories, $gmo_free, $gluten_free, $lactose_free, $vegan, $vegetarian, $meal_type, $recipe_id, $userID);
    $result->execute();

    // Since its kind of a pain to edit ingredients (can't simply do the UPDATE keyword), we just delete the ingredients and reinsert them
    // Same for the steps
    $conn->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
    $conn->prepare("DELETE FROM recipe_steps WHERE recipe_id = ?")->execute([$recipe_id]);

    foreach($recipe_ingredients as $ingredient) {
        $ingredient = trim($ingredient);
        $check = $conn->prepare("SELECT ingredient_id FROM ingredients WHERE ingredient_name = ?");
        $check->bind_param('s', $ingredient);
        $check->execute();
        $check->store_result();
        if($check->num_rows > 0) {
            $check->bind_result($ingredient_id);
            $check->fetch();
        } else {
            $ins = $conn->prepare("INSERT INTO ingredients (ingredient_name) VALUES (?)");
            $ins->bind_param('s', $ingredient);
            $ins->execute();
            $ingredient_id = $conn->insert_id;
        }
        $check->close();
        $ri = $conn->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_id) VALUES (?, ?)");
        $ri->bind_param('ii', $recipe_id, $ingredient_id);
        $ri->execute();
    }

    foreach($recipe_steps as $index => $step) {
        $step        = trim($step);
        $step_number = $index + 1;
        $rs = $conn->prepare("INSERT INTO recipe_steps (recipe_id, step_number, step_instruction) VALUES (?, ?, ?)");
        $rs->bind_param('iis', $recipe_id, $step_number, $step);
        $rs->execute();
    }
}

function createRecipe($userId, $recipe_ingredients_string, $meal_type){
    require 'api_config.php';
    global $conn, $ANTHROPIC_API_KEY;
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
        "model" => "claude-haiku-4-5-20251001",
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
    return $recipe;
}
?>