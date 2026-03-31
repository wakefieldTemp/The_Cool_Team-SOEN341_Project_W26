# The Cool Team - SOEN 341 Project

A PHP/MySQL web application for meal planning, recipe management, and calorie tracking.

## Project Structure

```
project/
├── config/               # Database connection and API configuration
├── public/               # Publicly accessible static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Image assets
├── src/
│   ├── controllers/      # Handles user actions and form submissions
│   ├── models/           # Database query functions and API wrappers
│   └── views/            # HTML/PHP front-end pages
└── index.php             # Application entry point (login page)
```

## Requirements

- PHP 8.x
- MySQL
- Laragon (local development)
- Composer (for running tests)

## Setup

1. Clone the repository
2. Import the database schema from `/database`
3. Configure your database credentials in `config/login_page_config.php`
4. Add your Anthropic API key in `config/api_config.php`
5. Start Laragon and navigate to the project URL

## Running Tests

```bash
composer install
vendor\bin\phpunit
```