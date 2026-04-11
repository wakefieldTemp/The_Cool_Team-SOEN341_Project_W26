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

    public function testAddMealToSchedulePreventsDuplicateMealsAndSetsSessionError(): void
    {
        $sandbox = $this->makeDuplicateSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();

require __DIR__ . '/app/models/sql_meals_functions.php';

$conn = new FakeConnWithDuplicate();

addMealToSchedule($conn, 5, 22, 'Monday', 'Lunch');

file_put_contents(__DIR__ . '/duplicate_result.json', json_encode([
    'session_error' => $_SESSION['duplicate_error'] ?? null,
    'insert_executed' => file_exists(__DIR__ . '/insert_executed.txt'),
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner_duplicate.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner_duplicate.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $resultFile = $sandbox . '/duplicate_result.json';
        $this->assertFileExists($resultFile);

        $result = json_decode((string) file_get_contents($resultFile), true);
        $this->assertIsArray($result);

        $this->assertSame('You already have this recipe in your schedule.', $result['session_error']);
        $this->assertFalse($result['insert_executed']);
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

    private function makeDuplicateSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/weekly_plan_duplicate_test_' . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);
        mkdir($dir . '/app/models', 0777, true);

        file_put_contents($dir . '/app/models/sql_meals_functions.php', <<<'PHP'
<?php

function addMealToSchedule($conn, $userId, $recipe_id, $day, $meal_type){
    $check = $conn->prepare("SELECT schedule_id FROM meal_schedule WHERE user_id=? AND (recipe_id=? OR (day_of_week=? AND meal_type=?))");
    $check->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $ins = $conn->prepare("INSERT INTO meal_schedule (user_id, recipe_id, day_of_week, meal_type) VALUES (?,?,?,?)");
        $ins->bind_param('iiss', $userId, $recipe_id, $day, $meal_type);
        $ins->execute();
    } else {
        $_SESSION['duplicate_error'] = "You already have this recipe in your schedule.";
    }
}

class FakeDuplicateCheckStmt
{
    public int $num_rows = 1;

    public function bind_param($types, &...$vars): void
    {
    }

    public function execute(): void
    {
    }

    public function store_result(): void
    {
    }
}

class FakeInsertStmt
{
    public function bind_param($types, &...$vars): void
    {
    }

    public function execute(): void
    {
        file_put_contents(__DIR__ . '/../../insert_executed.txt', 'yes');
    }
}

class FakeConnWithDuplicate
{
    public function prepare(string $sql)
    {
        if (strpos($sql, 'SELECT schedule_id FROM meal_schedule') !== false) {
            return new FakeDuplicateCheckStmt();
        }

        if (strpos($sql, 'INSERT INTO meal_schedule') !== false) {
            return new FakeInsertStmt();
        }

        return new FakeDuplicateCheckStmt();
    }
}
PHP);

        return $dir;
    }
}
