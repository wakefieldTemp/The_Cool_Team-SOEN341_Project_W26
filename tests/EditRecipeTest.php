<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class EditRecipeTest.php extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../Sprint Planning/Code/edit_recipe.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real script path.');
    }

    public function testRedirectsToRecipesWhenRecipeIdIsMissing(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

register_shutdown_function(function () {
    file_put_contents(__DIR__ . '/headers.json', json_encode(headers_list(), JSON_PRETTY_PRINT));
});

session_start();
$_SESSION['user_id'] = 12;

include __DIR__ . '/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $headersFile = $sandbox . '/headers.json';
        $this->assertFileExists($headersFile);

        $headers = json_decode((string) file_get_contents($headersFile), true);
        $this->assertIsArray($headers);

        $this->assertContains('Location: recipes.php', $headers);
    }

    public function testSaveRecipeCallsEditRecipeWithNormalizedValues(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

register_shutdown_function(function () {
    file_put_contents(__DIR__ . '/headers.json', json_encode(headers_list(), JSON_PRETTY_PRINT));
});

session_start();
$_SESSION['user_id'] = 7;

$_GET['recipe_id'] = 42;

$_POST['save_recipe'] = '1';
$_POST['recipe_name'] = '  Pasta Primavera  ';
$_POST['recipe_description'] = '  Fresh and easy  ';
$_POST['ingredients'] = json_encode(['pasta', 'peas', 'cream']);
$_POST['steps'] = json_encode(['Boil', 'Mix', 'Serve']);
$_POST['prep_time'] = '15';
$_POST['cook_time'] = '20';
$_POST['difficulty'] = 'medium';
$_POST['meal_type'] = 'dinner';
$_POST['calories'] = '650';
$_POST['dietary_tags'] = ['gluten_free', 'vegetarian'];

include __DIR__ . '/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/editRecipe_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertIsArray($args);

        $this->assertSame(7, $args[0]);                       // $userId
        $this->assertSame(42, $args[1]);                      // $recipe_id
        $this->assertSame('Pasta Primavera', $args[2]);       // trimmed name
        $this->assertSame('Fresh and easy', $args[3]);        // trimmed description
        $this->assertSame(15, $args[4]);                      // prep_time intval
        $this->assertSame(20, $args[5]);                      // cook_time intval
        $this->assertSame('medium', $args[6]);
        $this->assertSame(650, $args[7]);                     // calories intval
        $this->assertSame(0, $args[8]);                       // gmo_free
        $this->assertSame(1, $args[9]);                       // gluten_free
        $this->assertSame(0, $args[10]);                      // lactose_free
        $this->assertSame(0, $args[11]);                      // vegan
        $this->assertSame(1, $args[12]);                      // vegetarian
        $this->assertSame('dinner', $args[13]);
        $this->assertSame(['pasta', 'peas', 'cream'], $args[14]);
        $this->assertSame(['Boil', 'Mix', 'Serve'], $args[15]);

        $headers = json_decode((string) file_get_contents($sandbox . '/headers.json'), true);
        $this->assertContains('Location: recipes.php', $headers);
    }

    public function testPageLoadsExistingRecipeIngredientsAndSteps(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 99;
$_GET['recipe_id'] = 5;

ob_start();
include __DIR__ . '/subject.php';
$html = ob_get_clean();

file_put_contents(__DIR__ . '/page.html', $html);
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $htmlFile = $sandbox . '/page.html';
        $this->assertFileExists($htmlFile);

        $html = (string) file_get_contents($htmlFile);

        $this->assertStringContainsString('Edit Recipe', $html);
        $this->assertStringContainsString('value="Mock Recipe"', $html);
        $this->assertStringContainsString('A mocked description', $html);
        $this->assertStringContainsString('tomato', $html);
        $this->assertStringContainsString('basil', $html);
        $this->assertStringContainsString('Chop ingredients', $html);
        $this->assertStringContainsString('Cook gently', $html);
        $this->assertStringContainsString('value="breakfast" selected', $html);
        $this->assertStringContainsString('value="easy"   selected', $html);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/edit_recipe_test_' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        copy($this->sourceFile, $dir . '/subject.php');

        $loginPageConfig = <<<'PHP'
<?php

class FakeResultSet
{
    private array $rows;
    private int $index = 0;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function fetch_assoc()
    {
        if ($this->index >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->index++];
    }
}

class FakeStmt
{
    private string $sql;
    private array $boundValues = [];

    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    public function bind_param($types, &...$vars): void
    {
        $this->boundValues = &$vars;
    }

    public function execute(): void
    {
    }

    public function get_result()
    {
        if (strpos($this->sql, 'SELECT * FROM recipes') !== false) {
            return new FakeResultSet([[
                'recipe_id' => 5,
                'recipe_name' => 'Mock Recipe',
                'description' => 'A mocked description',
                'prep_time' => 10,
                'cook_time' => 25,
                'difficulty_level' => 'easy',
                'meal_type' => 'breakfast',
                'calories' => 400,
                'gmo_free' => 1,
                'gluten_free' => 0,
                'lactose_free' => 1,
                'vegan' => 0,
                'vegetarian' => 1,
            ]]);
        }

        if (strpos($this->sql, 'SELECT i.ingredient_name FROM ingredients i JOIN recipe_ingredients ri') !== false) {
            return new FakeResultSet([
                ['ingredient_name' => 'tomato'],
                ['ingredient_name' => 'basil'],
            ]);
        }

        if (strpos($this->sql, 'SELECT step_instruction FROM recipe_steps') !== false) {
            return new FakeResultSet([
                ['step_instruction' => 'Chop ingredients'],
                ['step_instruction' => 'Cook gently'],
            ]);
        }

        return new FakeResultSet([]);
    }
}

class FakeConn
{
    public function prepare(string $sql)
    {
        return new FakeStmt($sql);
    }
}

$conn = new FakeConn();
PHP;

        file_put_contents($dir . '/login_page_config.php', $loginPageConfig);

        $sqlRecipeFunctions = <<<'PHP'
<?php

function editRecipe(...$args): void
{
    file_put_contents(__DIR__ . '/editRecipe_log.json', json_encode($args, JSON_PRETTY_PRINT));
}
PHP;

        file_put_contents($dir . '/sql_recipe_functions.php', $sqlRecipeFunctions);

        return $dir;
    }
}
