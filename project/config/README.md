# config/

This folder contains application-wide configuration files shared across all PHP scripts.

## Files

### `login_page_config.php`
Establishes the MySQL database connection using `mysqli`. Defines the `BASE_URL` constant used throughout the app for building correct URLs and redirects. Every PHP file that needs a database connection or uses `BASE_URL` requires this file.

### `api_config.php`
Stores the Anthropic API key used by the recipe creation and calorie tip features. Required by any file that makes calls to the Claude API.

## Usage

From a file in `src/views/` or `src/controllers/`:
```php
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../../config/api_config.php';
```

From a file in `src/models/`:
```php
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../../config/api_config.php';
```