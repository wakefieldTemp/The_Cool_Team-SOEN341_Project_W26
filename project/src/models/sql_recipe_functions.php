<?php
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

function deleteRecipe($recipe_id, $userId){
    global $conn;
    $delete_query = "DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('ii', $recipe_id, $userId);
    $delete_stmt->execute();
}

function recipeDisplayInformation($userId, $search_name, $prep_time_filter, $cook_time_filter, 
                                  $order_by, $order_direction){
    global $conn;
    // The main query, we will add stuff depending on the searching, filtering and sorting
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

    // The tags and the difficulty
    $filter_gmo_free     = isset($_GET['filter_gmo_free'])     ? 1 : null;
    $filter_gluten_free  = isset($_GET['filter_gluten_free'])  ? 1 : null;
    $filter_lactose_free = isset($_GET['filter_lactose_free']) ? 1 : null;
    $filter_vegan        = isset($_GET['filter_vegan'])        ? 1 : null;
    $filter_vegetarian   = isset($_GET['filter_vegetarian'])   ? 1 : null;

    $filter_easy_diff   = isset($_GET['easy_diff'])   ? 1 : null;
    $filter_medium_diff = isset($_GET['medium_diff']) ? 1 : null;
    $filter_hard_diff   = isset($_GET['hard_diff'])   ? 1 : null;

    // Add the tags and difficulty filters to the query
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

    // Here is for the search (% is for wildcard)
    if ($search_name) {
        $sql_query .= " AND recipe_name LIKE ?";
        $params[]   = '%' . $search_name . '%';
        $types     .= "s";
    }

    $sql_query .= " ORDER BY $order_by $order_direction";

    $result = $conn->prepare($sql_query);
    $result->bind_param($types, ...$params);
    $result->execute();
    return $result->get_result();
}

function getRecipeInformation($recipe_id, $userId){
    global $conn;
    $result = $conn->prepare("SELECT * FROM recipes WHERE recipe_id = ? AND user_id = ?");
    $result->bind_param('ii', $recipe_id, $userId);
    $result->execute();
    return $result->get_result()->fetch_assoc();
}

function getRecipeIngredients($recipe_id){
    global $conn;
    $ingredient_result = $conn->prepare("SELECT i.ingredient_name FROM ingredients i JOIN recipe_ingredients ri ON i.ingredient_id = ri.ingredient_id WHERE ri.recipe_id = ?");
    $ingredient_result->bind_param('i', $recipe_id);
    $ingredient_result->execute();
    return $ingredient_result->get_result();
}

function getRecipeSteps($recipe_id){
    global $conn;
    $step_result = $conn->prepare("SELECT step_instruction FROM recipe_steps WHERE recipe_id = ? ORDER BY step_number");
    $step_result->bind_param('i', $recipe_id);
    $step_result->execute();
    return $step_result->get_result();
}

function getRecipesWithStepNumber($recipe_id){
    global $conn;
    $step_query = "SELECT step_number, step_instruction
                    FROM recipe_steps
                    WHERE recipe_id = ?";
        $step_stmt = $conn->prepare($step_query);
        $step_stmt->bind_param('i', $recipe_id);
        $step_stmt->execute();  
        return $step_stmt->get_result();
}

function createRecipe($userId, $recipe_ingredients_string, $meal_type){
    require_once __DIR__ . '/../../config/api_config.php';
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