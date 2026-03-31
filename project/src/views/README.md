# src/views/

This folder contains the front-end PHP pages of the application. Files here are mostly HTML with embedded PHP for displaying dynamic data. They do not handle form submissions directly — those are handled by controllers.

## Files

### `index.php`
The application entry point and login/registration page. Located at the project root rather than in this folder. Displays the login and register forms and handles toggling between them.

### `main_menu.php`
The main dashboard after login. Displays the weekly meal schedule as a grid, allows adding and removing meals per day and meal type, and provides navigation to all other pages via a sidebar.

### `recipes.php`
Displays all of the user's saved recipes with search, sort, and filter functionality. Each recipe card expands on click to show full details, ingredients, and steps. Links to `edit_recipe.php` and handles delete via POST.

### `recipe_creation.php`
Allows the user to input ingredients and a meal type, then uses the Claude API to generate a recipe. Displays the generated recipe and gives the option to save it.

### `calorie_tracker.php`
Shows the user's daily calorie intake vs their goal. Allows manually adding or removing calories. Displays an AI-generated motivational tip via the Claude API.

### `profile.php`
Displays and manages the user's profile including allergies, dietary preferences, and daily calorie goal.

## Notes

- All views require `config/login_page_config.php` for `$conn` and `BASE_URL`
- Views use `BASE_URL` for all links, image sources, and stylesheet hrefs