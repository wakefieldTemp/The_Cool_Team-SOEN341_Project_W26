# src/controllers/

This folder contains PHP files that handle user actions, form submissions, and business logic. Controllers process input, call model functions, and redirect to the appropriate view.

## Files

### `add_recipe.php`
Handles the form submission for manually adding a new recipe. Collects recipe details, ingredients, and steps from POST data, calls `addRecipe()` from the models, then redirects to `recipes.php`.

### `edit_recipe.php`
Loads an existing recipe by `recipe_id` from the URL, displays it pre-filled in a form, and handles the save submission by calling `editRecipe()`. Redirects to `recipes.php` on save.

### `login_page_register.php`
Handles both login and registration form submissions from `index.php`. Validates credentials, manages sessions, and redirects accordingly.

### `log_out.php`
Destroys the current session and redirects the user back to `index.php`.

## Notes

- All controllers require `login_page_config.php` for the database connection and `BASE_URL`
- After processing, controllers always redirect using `header()` + `exit()` to follow the Post/Redirect/Get pattern