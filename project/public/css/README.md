# public/css/

This folder contains all stylesheets for the application. Each CSS file corresponds to a specific page or feature.

## Files

| File | Used By |
|------|---------|
| `login_page_style.css` | `index.php` — login and registration page |
| `main_menu_style.css` | `main_menu.php` — dashboard and weekly schedule |
| `recipes_style.css` | `recipes.php` — recipe listing page |
| `add_recipe_style.css` | `add_recipe.php`, `edit_recipe.php` — recipe form pages |
| `recipe_creation_style.css` | `recipe_creation.php` — AI recipe generator page |
| `calorie_tracker_style.css` | `calorie_tracker.php` — calorie tracking page |
| `profile_page_style.css` | `profile.php` — user profile page |

## Usage

Link stylesheets in your PHP views using `BASE_URL`:
```html
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/filename.css">
```