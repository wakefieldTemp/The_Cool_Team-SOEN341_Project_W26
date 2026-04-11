<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WeeklyPlanTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/controllers/main_menu_post.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real weekly plan script path.');
    }

    public function testAddActionAssignsRecipeToDayAndMealType(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 14;

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'add';
$_POST['recipe_id'] = '25';
$_POST['day_of_week'] = 'Wednesday';
$_POST['meal_type'] = 'Dinner';

include __DIR__ . '/app/views/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/addMealToSchedule_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertIsArray($args);

        // [conn_marker, userId, recipeId, day, mealType]
        $this->assertSame('fake-conn', $args[0]);
        $this->assertSame(14, $args[1]);
        $this->assertSame(25, $args[2]);
        $this->assertSame('Wednesday', $args[3]);
        $this->assertSame('Dinner', $args[4]);
    }

    public function testScheduleArrayIsBuiltFromMealsGroupedByDay(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 8;
$_SERVER['REQUEST_METHOD'] = 'GET';

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/schedule_dump.json', json_encode($schedule, JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $scheduleFile = $sandbox . '/schedule_dump.json';
        $this->assertFileExists($scheduleFile);

        $schedule = json_decode((string) file_get_contents($scheduleFile), true);
        $this->assertIsArray($schedule);

        $this->assertArrayHasKey('Monday', $schedule);
        $this->assertArrayHasKey('Wednesday', $schedule);
        $this->assertArrayHasKey('Sunday', $schedule);

        $this->assertSame('Breakfast', $schedule['Monday'][0]['meal_type']);
        $this->assertSame('Oatmeal Bowl', $schedule['Monday'][0]['recipe_name']);

        $this->assertSame('Dinner', $schedule['Wednesday'][0]['meal_type']);
        $this->assertSame('Veggie Pasta', $schedule['Wednesday'][0]['recipe_name']);
    }

    public function testRecipeDropdownListIsLoadedForUser(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 8;
$_SERVER['REQUEST_METHOD'] = 'GET';

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/recipe_list_dump.json', json_encode($user_recipe_list, JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $recipeListFile = $sandbox . '/recipe_list_dump.json';
        $this->assertFileExists($recipeListFile);

        $recipeList = json_decode((string) file_get_contents($recipeListFile), true);
        $this->assertIsArray($recipeList);

        $this->assertCount(2, $recipeList);
        $this->assertSame(101, $recipeList[0]['recipe_id']);
        $this->assertSame('Apple Toast', $recipeList[0]['recipe_name']);
        $this->assertSame(102, $recipeList[1]['recipe_id']);
        $this->assertSame('Veggie Pasta', $recipeList[1]['recipe_name']);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/weekly_plan_test_' . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);
        mkdir($dir . '/config', 0777, true);
        mkdir($dir . '/app/views', 0777, true);
        mkdir($dir . '/app/models', 0777, true);

        copy($this->sourceFile, $dir . '/app/views/subject.php');

        file_put_contents($dir . '/config/login_page_config.php', <<<'PHP'
<?php

define('BASE_URL', 'http://example.test');

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

    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    public function bind_param($types, &...$params): void
    {
    }

    public function execute(): void
    {
    }

    public function get_result()
    {
        if (strpos($this->sql, 'SELECT recipe_id, recipe_name FROM recipes') !== false) {
            return new FakeResultSet([
                ['recipe_id' => 101, 'recipe_name' => 'Apple Toast'],
                ['recipe_id' => 102, 'recipe_name' => 'Veggie Pasta'],
            ]);
        }

        return new FakeResultSet([]);
    }
}

class FakeConn
{
    public string $marker = 'fake-conn';

    public function prepare(string $sql)
    {
        return new FakeStmt($sql);
    }
}

$conn = new FakeConn();
PHP);

        file_put_contents($dir . '/app/models/sql_meals_functions.php', <<<'PHP'
<?php

function addMealToSchedule($conn, $userId, $recipeId, $day, $mealType): void
{
    file_put_contents(
        __DIR__ . '/../../addMealToSchedule_log.json',
        json_encode([$conn->marker, $userId, $recipeId, $day, $mealType], JSON_PRETTY_PRINT)
    );
}

function deleteMealFromSchedule($conn, $userId, $scheduleId): void
{
}

function getMealsForSchedule($conn, $userId)
{
    return new FakeResultSet([
        [
            'schedule_id' => 1,
            'day_of_week' => 'Monday',
            'meal_type' => 'Breakfast',
            'recipe_name' => 'Oatmeal Bowl',
            'recipe_id' => 201,
        ],
        [
            'schedule_id' => 2,
            'day_of_week' => 'Wednesday',
            'meal_type' => 'Dinner',
            'recipe_name' => 'Veggie Pasta',
            'recipe_id' => 202,
        ],
    ]);
}
PHP);

        return $dir;
    }
}
