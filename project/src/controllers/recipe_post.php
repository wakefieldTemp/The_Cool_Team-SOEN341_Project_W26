<?php
session_start();
require_once __DIR__ . '/../../config/login_page_config.php';
require_once __DIR__ . '/../models/sql_recipe_functions.php';
$userId = $_SESSION['user_id'];

// If delete recipe button is clicked (works the same as the allergies and dp)
if(isset($_POST['delete_recipe'])) {
    $recipe_id = $_POST['recipe_id'];
    deleteRecipe($recipe_id, $userId);
}

// Here is for the filtering, searching and sorting
$search_name = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? '';
$cook_time_filter = $_GET['cook_time_filter'] ?? '';
$prep_time_filter = $_GET['prep_time_filter'] ?? '';
// Basically a bunch of switch statements to figure our the sorting and filtering
switch($cook_time_filter) {
    case 'under_15':
        $cook_time_filter = 15;
        break;
    case 'under_30':
        $cook_time_filter = 30;
        break;
    case 'under_60':
        $cook_time_filter = 60;
        break;
    case 'over_60':
        $cook_time_filter = 1000;
        break;
    default:
        $cook_time_filter = 1000;
}

switch($prep_time_filter) {
    case 'under_15':
        $prep_time_filter = 15;
        break;
    case 'under_30':
        $prep_time_filter = 30;
        break;
    case 'under_60':
        $prep_time_filter = 60;
        break;
    case 'over_60':
        $prep_time_filter = 1000;
        break;
    default:
        $prep_time_filter = 1000;
}

// Here the two variables are for what we're sorting for, and in what order
switch($sort_by) {
    case 'name_desc':
        $order_by = 'recipe_name';
        $order_direction = 'DESC';
        break;
    case 'name_asc':
        $order_by = 'recipe_name';
        $order_direction = 'ASC';
        break;
    case 'cook_time_desc':
        $order_by = 'cook_time';
        $order_direction = 'DESC';
        break;
    case 'cook_time_asc':
        $order_by = 'cook_time';
        $order_direction = 'ASC';
        break;
    case 'prep_time_desc':
        $order_by = 'prep_time';
        $order_direction = 'DESC';
        break;
    case 'prep_time_asc':
        $order_by = 'prep_time';
        $order_direction = 'ASC';
        break;
    default:
        $order_by = 'recipe_name';
        $order_direction = 'ASC';
}


$recipes = recipeDisplayInformation($userId, $search_name, $prep_time_filter, $cook_time_filter, 
                                  $order_by, $order_direction);
?>