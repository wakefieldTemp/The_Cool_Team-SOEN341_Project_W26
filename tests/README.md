# tests/

This folder contains PHPUnit tests for the application. Tests are written using a sandbox pattern — each test copies the source file into a temporary directory, patches the require paths, and runs it in isolation using a subprocess.

## Files

### `SearchRecipeTest.php`
Tests the search, filter, and sorting logic in `recipes.php`. Verifies that the correct SQL query is built and the correct parameters are bound depending on the GET inputs provided.

| Test | Description |
|------|-------------|
| `testDefaultSearchUsesDefaultFiltersAndSorting` | No filters applied — expects default query with `prep_time <= 1000`, `cook_time <= 1000`, and `ORDER BY recipe_name ASC` |
| `testSearchByNameAddsLikeClauseAndStringParameter` | Search by name — expects `LIKE ?` clause and `%pasta%` parameter |
| `testFiltersAndSortingAreAddedToQuery` | Multiple filters and sort — expects all conditions present in SQL |
| `testOverSixtyMapsToOneThousandAndNameDescendingSort` | `over_60` filter maps to 1000, sort by name descending |

### `DeleteRecipeTest.php`
Tests the delete recipe logic in `recipes.php`. Verifies the correct SQL is prepared, parameters are bound as integers, the statement is executed, and that delete is not triggered when the POST flag is missing.

| Test | Description |
|------|-------------|
| `testDeleteRecipePreparesCorrectDeleteQuery` | Correct DELETE SQL is prepared |
| `testDeleteRecipeBindsRecipeIdAndUserIdAsIntegers` | Bind types are `ii` with correct values |
| `testDeleteRecipeExecutesDeleteStatement` | `execute()` is called on the statement |
| `testDeleteIsNotTriggeredWhenDeletePostFlagIsMissing` | No delete files created when `delete_recipe` POST flag is absent |

### `CreateRecipeTest.php`
Tests the recipe creation logic in `recipe_creation.php`. Verifies that ingredients are decoded correctly, `createRecipe()` is called with the right arguments, and `addRecipe()` is called with normalized values on save.

| Test | Description |
|------|-------------|
| `testCreateRecipeUsesDecodedIngredientsAndMealType` | Ingredients JSON is decoded and passed as a comma-separated string |
| `testCreateRecipeFallsBackToEmptyArrayWhenIngredientsJsonIsInvalid` | Invalid JSON falls back to empty array and empty string |
| `testSaveRecipeCallsAddRecipeWithNormalizedValues` | `addRecipe()` is called with correctly typed and cast values |

### `EditRecipeTest.php`
Tests the edit recipe logic in `edit_recipe.php`. Verifies redirects when recipe ID is missing, correct values passed to `editRecipe()`, and that the page renders existing recipe data correctly.

| Test | Description |
|------|-------------|
| `testRedirectsToRecipesWhenRecipeIdIsMissing` | Script exits immediately when no `recipe_id` is in GET |
| `testSaveRecipeCallsEditRecipeWithNormalizedValues` | `editRecipe()` receives trimmed, cast, and correctly typed values |
| `testPageLoadsExistingRecipeIngredientsAndSteps` | Page HTML contains the mocked recipe name, ingredients, and steps |

## Running Tests

Make sure Composer is installed and dependencies are set up:
```bash
composer install
```

Run all tests from the project root:
```bash
vendor\bin\phpunit
```

## How the Sandbox Works

Each `makeSandbox()` method:
1. Creates a temporary directory
2. Copies the source file into it as `subject.php`
3. Patches all `require_once` paths so they point to local stubs
4. Provides fake implementations of `$conn`, model functions, and `BASE_URL`
5. Runs a `runner.php` script via subprocess that includes `subject.php` and writes results to files
6. The test then reads those files and asserts on their contents