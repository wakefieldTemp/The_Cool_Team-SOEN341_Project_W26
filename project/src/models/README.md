# src/models/

This folder contains PHP files with reusable functions for database queries and external API calls. No HTML is output from these files.

## Files

### `sql_recipe_functions.php`
Functions for managing recipes in the database.
- `addRecipe()` — inserts a new recipe with ingredients and steps
- `editRecipe()` — updates an existing recipe, replacing its ingredients and steps
- `createRecipe()` — calls the Claude API to generate a recipe from a list of ingredients

### `sql_calorie_functions.php`
Functions for managing the calorie tracker.
- `addCalories()` / `removeCalories()` — updates the user's daily calorie total
- `checkCalories()` — initializes or resets the daily calorie row
- `getTotalCalories()` — fetches the user's current calorie count
- `getDailyGoal()` — fetches the user's calorie goal

### `sql_meals_functions.php`
Functions for managing the weekly meal schedule.
- `addMealToSchedule()` — adds a recipe to a specific day and meal type
- `deleteMealFromSchedule()` — removes a meal from the schedule
- `getMealsForSchedule()` — fetches all scheduled meals for a user

### `api_calorie_functions.php`
Wrapper for the Claude API calorie tip feature.
- `getCalorieTip()` — sends the user's calorie data to the API and returns a motivational message

## Notes

- All SQL functions receive `$conn` as a parameter (except recipe functions which use `global $conn`)
- Requires `config/login_page_config.php` and/or `config/api_config.php` depending on the file