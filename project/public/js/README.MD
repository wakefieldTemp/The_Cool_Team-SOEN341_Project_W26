# public/js/

This folder contains JavaScript files for the application. Most page-specific scripts are written inline at the bottom of their respective PHP files — only shared or standalone scripts live here.

## Files

### `login_page_script.js`
Handles toggling between the login and register forms on `index.php`. Exposes a `showForm()` function called by the anchor links on each form.

### `main_menu_script.js`
Handles client-side interactivity for the main menu/dashboard page. Includes behaviour for the sidebar toggle and the meal schedule forms (adding/removing meals per day and meal type).

### `recipes_script.js`
Handles client-side interactivity for `recipes.php`. Manages recipe card expand/collapse on click, and any dynamic filtering, sorting, or search behaviour on the recipes list.

## Usage

Include scripts in your PHP views using `BASE_URL`:
```html
<script src="<?= BASE_URL ?>/public/js/login_page_script.js"></script>
```

## Notes

- Scripts that have grown large enough or are shared across pages are kept here
- Smaller or truly page-specific scripts may still be written inline at the bottom of their respective view files