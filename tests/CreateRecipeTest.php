<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CreateRecipeTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/views/recipe_creation.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real script path.');
    }

    public function testCreateRecipeUsesDecodedIngredientsAndMealType(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);
session_start();
$_SESSION['user_id'] = 99;

$_POST['create_recipe'] = '1';
$_POST['ingredients'] = json_encode(['tomato', 'rice', 'beans']);
$_POST['meal_type'] = 'dinner';

include __DIR__ . '/subject.php';

file_put_contents(
    __DIR__ . '/result.json',
    json_encode([
        'show_current_recipe' => $show_current_recipe,
        'recipe' => $recipe,
        'recipe_ingredients' => $recipe_ingredients,
        'meal_type' => $meal_type,
        'createRecipeArgs' => $GLOBALS['createRecipeArgs'] ?? null,
    ], JSON_PRETTY_PRINT)
);
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Runner failed: ' . implode("\n", $output));

        $resultFile = $sandbox . '/result.json';
        $this->assertFileExists($resultFile, 'Result file was not created.');

        $data = json_decode((string) file_get_contents($resultFile), true);
        $this->assertIsArray($data, 'Decoded result.json is not an array.');

        $this->assertTrue($data['show_current_recipe']);
        $this->assertSame(['tomato', 'rice', 'beans'], $data['recipe_ingredients']);
        $this->assertSame('dinner', $data['meal_type']);

        $this->assertSame(99, $data['createRecipeArgs'][0]);
        $this->assertSame('tomato, rice, beans', $data['createRecipeArgs'][1]);
        $this->assertSame('dinner', $data['createRecipeArgs'][2]);
    }

    public function testCreateRecipeFallsBackToEmptyArrayWhenIngredientsJsonIsInvalid(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);
session_start();
$_SESSION['user_id'] = 55;

$_POST['create_recipe'] = '1';
$_POST['ingredients'] = 'not valid json';
$_POST['meal_type'] = 'lunch';

include __DIR__ . '/subject.php';

file_put_contents(
    __DIR__ . '/result.json',
    json_encode([
        'recipe_ingredients' => $recipe_ingredients,
        'createRecipeArgs' => $GLOBALS['createRecipeArgs'] ?? null,
    ], JSON_PRETTY_PRINT)
);
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Runner failed: ' . implode("\n", $output));

        $resultFile = $sandbox . '/result.json';
        $this->assertFileExists($resultFile, 'Result file was not created.');

        $data = json_decode((string) file_get_contents($resultFile), true);
        $this->assertIsArray($data, 'Decoded result.json is not an array.');

        $this->assertSame([], $data['recipe_ingredients']);
        $this->assertSame('', $data['createRecipeArgs'][1]);
        $this->assertSame('lunch', $data['createRecipeArgs'][2]);
    }

    public function testSaveRecipeCallsAddRecipeWithNormalizedValues(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);
session_start();
$_SESSION['user_id'] = 7;

$_POST['save_recipe'] = '1';
$_POST['meal_type'] = 'breakfast';
$_POST['recipe_data'] = json_encode([
    'name' => 'Protein Oats',
    'description' => 'Quick breakfast',
    'prep_time_minutes' => '5',
    'cook_time_minutes' => '3',
    'difficulty' => 'easy',
    'calories' => '350',
    'gmo_free' => true,
    'gluten_free' => false,
    'lactose_free' => true,
    'vegan' => false,
    'vegetarian' => true,
    'ingredients' => ['oats', 'milk'],
    'steps' => ['mix', 'cook']
]);

include __DIR__ . '/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, 'Runner failed: ' . implode("\n", $output));

        $logFile = $sandbox . '/addRecipe_log.json';
        $this->assertFileExists($logFile, 'addRecipe log file was not created.');

        $log = json_decode((string) file_get_contents($logFile), true);
        $this->assertIsArray($log, 'Decoded addRecipe_log.json is not an array.');

        $this->assertSame(7, $log[0]);
        $this->assertSame('Protein Oats', $log[1]);
        $this->assertSame('Quick breakfast', $log[2]);
        $this->assertSame(5, $log[3]);
        $this->assertSame(3, $log[4]);
        $this->assertSame('easy', $log[5]);
        $this->assertSame(350, $log[6]);
        $this->assertSame(1, $log[7]);
        $this->assertSame(0, $log[8]);
        $this->assertSame(1, $log[9]);
        $this->assertSame(0, $log[10]);
        $this->assertSame(1, $log[11]);
        $this->assertSame('breakfast', $log[12]);
        $this->assertSame(['oats', 'milk'], $log[13]);
        $this->assertSame(['mix', 'cook'], $log[14]);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/recipe_test_' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        copy($this->sourceFile, $dir . '/subject.php');

        // Patch require paths for sandbox
        $subject = file_get_contents($dir . '/subject.php');
        $subject = str_replace(
            "require_once __DIR__ . '/../../config/login_page_config.php'",
            "require_once __DIR__ . '/login_page_config.php'",
            $subject
        );
        $subject = str_replace(
            "require_once __DIR__ . '/../../config/api_config.php'",
            "require_once __DIR__ . '/api_config.php'",
            $subject
        );
        $subject = str_replace(
            "require_once __DIR__ . '/../models/sql_recipe_functions.php'",
            "require_once __DIR__ . '/sql_recipe_functions.php'",
            $subject
        );
        file_put_contents($dir . '/subject.php', $subject);

        file_put_contents($dir . '/api_config.php', "<?php\n");
        file_put_contents($dir . '/login_page_config.php', "<?php\ndefine('BASE_URL', '');\n");

        $stubs = <<<'PHP'
<?php

function createRecipe($userId, $recipeIngredientsString, $mealType) {
    $GLOBALS['createRecipeArgs'] = func_get_args();
    return ['generated' => true];
}

function addRecipe(...$args) {
    file_put_contents(__DIR__ . '/addRecipe_log.json', json_encode($args, JSON_PRETTY_PRINT));
}
PHP;

        file_put_contents($dir . '/sql_recipe_functions.php', $stubs);

        return $dir;
    }
}