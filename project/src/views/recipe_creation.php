<?php include __DIR__ . '/../controllers/recipe_creation_post.php'; ?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recipe Creation</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/recipe_creation_style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="menu-button">
        <a href="<?= BASE_URL ?>/src/views/main_menu.php">
            <i class='bx bx-arrow-back'></i> Back to Main Menu
        </a>
    </div>
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