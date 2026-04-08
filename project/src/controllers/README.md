# src/controllers/

This folder contains PHP files that handle user actions, form submissions, and business logic. Controllers process input, call model functions, and redirect to the appropriate view.

## Files

### `add_recipe.php`
Handles the form submission for manually adding a new recipe. Collects recipe details, ingredients, and steps from POST data, calls `addRecipe()` from the models, then redirects to `recipes.php`.

### `allergy_post.php`
Handles form submissions for updating the user's allergy information. Calls the relevant model function and redirects to `profile.php`.

### `calorie_tracker_post.php`
Handles form submissions from the calorie tracker page. Processes calorie updates and redirects back to `calorie_tracker.php`.

### `calories_post.php`
Handles adding or removing calorie entries. Calls `addCalories()` or `removeCalories()` from the models and redirects accordingly.

### `edit_recipe.php`
Loads an existing recipe by `recipe_id` from the URL, displays it pre-filled in a form, and handles the save submission by calling `editRecipe()`. Redirects to `recipes.php` on save.

### `log_out.php`
Destroys the current session and redirects the user back to `index.php`.

### `login_page_register.php`
Handles both login and registration form submissions from `index.php`. Validates credentials, manages sessions, and redirects accordingly.

### `main_menu_post.php`
Handles form submissions from the main menu/dashboard, such as adding or removing meals from the weekly schedule. Calls the relevant meal schedule model functions and redirects to `main_menu.php`.

### `preference_post.php`
Handles form submissions for updating the user's dietary preferences. Calls the relevant model function and redirects to `profile.php`.

### `recipe_creation_post.php`
Handles the form submission from `recipe_creation.php`. Passes the user's ingredients and meal type to the Claude API via the model, then displays or saves the generated recipe.

### `recipe_post.php`
Handles general recipe-related POST actions (e.g., deleting a recipe). Calls the appropriate model function and redirects to `recipes.php`.

## Notes

- All controllers require `login_page_config.php` for the database connection and `BASE_URL`
- After processing, controllers always redirect using `header()` + `exit()` to follow the Post/Redirect/Get pattern