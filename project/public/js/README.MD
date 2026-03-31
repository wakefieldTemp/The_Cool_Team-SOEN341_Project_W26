# public/js/

This folder contains JavaScript files for the application. Most page-specific scripts are written inline at the bottom of their respective PHP files — only shared or standalone scripts live here.

## Files

### `login_page_script.js`
Handles toggling between the login and register forms on `index.php`. Exposes a `showForm()` function called by the anchor links on each form.

## Usage

Include scripts in your PHP views using `BASE_URL`:
```html
<script src="<?= BASE_URL ?>/public/js/login_page_script.js"></script>
```

## Notes

- Page-specific scripts (recipe ingredient/step management, sidebar toggle, meal schedule forms) are written inline at the bottom of their respective view files
- If a script grows large or needs to be shared across pages, move it here