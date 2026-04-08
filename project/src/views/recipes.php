<?php include __DIR__ . '/../controllers/recipe_post.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recipes</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/recipes_style.css">
</head>

<header class="site-header">
    <div class="brand">
        <img class="logo" src="<?= BASE_URL ?>/public/images/logo.jpg" alt="Logo">
        <div class="title">The Cool Team App</div>
    </div>

    <div class="back-button-container">
        <button class="btn btn-primary" onclick="window.location.href='<?= BASE_URL ?>/src/views/main_menu.php'">
            Back to Main Page
        </button>
    </div>
</header>

<body>
    <a href="<?= BASE_URL ?>/src/views/add_recipe.php" class="add-recipe-btn" title="Add Recipes" aria-label="Add Recipes">Add Recipes</a>
    <div class="card">
        <h2>My Recipes</h2>
        <div class="recipes-container">
            <form method="get" action=""> <!-- All in a get method, so we can get the values directly without submitting anything -->
            <!-- Here is for the searching -->
            <div class="search-section">
                <h1>Search</h1>
                <input type="text" name="search" placeholder="Search recipes..." value="<?=htmlspecialchars($_GET['search'] ?? '')?>">
            </div>
            <!-- Here is for all the sorting (different options) -->
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
            <!-- Here is for all the filter section -->
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
            /*
            Basically here we're gonna go through each recipe for the current user
            Then for each recipe, were gonna get all the ingredients and steps from that recipe
            Then we're gonna display all the information of the recipe
            */
if ($recipes->num_rows > 0) {
    while ($row = $recipes->fetch_assoc()) {
        $recipe_id = $row['recipe_id'];
        $ingredients_result = getRecipeIngredients($recipe_id);
        $ingredients = [];
        while ($ingredient = $ingredients_result->fetch_assoc()) {
            $ingredients[] = htmlspecialchars($ingredient['ingredient_name']);
        }
        $ingredients_display = !empty($ingredients) ? implode(', ', $ingredients) : 'No ingredients listed.';
        $steps_result = getRecipesWithStepNumber($recipe_id);
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
                <div class="recipe-tags">' . $tags_display . '</div>
                <div class="recipe-ingredients">' . $ingredients_display . '</div>
                <div class="recipe-steps">' . $steps_display . '</div>
            </div>

            <div class="recipe-actions">
                <a href="' . BASE_URL . '/src/views/edit_recipe.php?recipe_id=' . $recipe_id . '">Edit</a>
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
    </div>

    <script>
        // Script function for cool hidden display (when you click the recipe it becomes bigger with all the information)
        function toggleRecipe(element) {
            element.classList.toggle('open');
        }
    </script>
</body>
</html>