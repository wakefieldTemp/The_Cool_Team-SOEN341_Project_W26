<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CaloriesPageTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/controllers/calorie_tracker_post.php';
        $this->assertFileExists($this->sourceFile, 'incorrect $sourceFile.');
    }

    public function testAddCaloriesCallsAddCaloriesWithUserCaloriesAndTodayDate(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 12;

$_POST['add_calories'] = '1';
$_POST['calories_added'] = '275';
$_SERVER['PHP_SELF'] = '/fake/calories.php';

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/finished.txt', 'reached-end');
PHP;

        file_put_contents($sandbox . '/runner_add.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner_add.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/addCalories_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertIsArray($args);

        $this->assertSame('fake-conn', $args[0]);
        $this->assertSame(12, $args[1]);
        $this->assertSame(275, $args[2]);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $args[3]);

        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    public function testRemoveCaloriesCallsRemoveCaloriesWithUserCaloriesAndTodayDate(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 21;

$_POST['remove_calories'] = '1';
$_POST['calories_removed'] = '120';
$_SERVER['PHP_SELF'] = '/fake/calories.php';

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/finished.txt', 'reached-end');
PHP;

        file_put_contents($sandbox . '/runner_remove.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner_remove.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/removeCalories_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertIsArray($args);

        $this->assertSame('fake-conn', $args[0]);
        $this->assertSame(21, $args[1]);
        $this->assertSame(120, $args[2]);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $args[3]);

        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    public function testDailyCalorieFunctionsAreCalledAndValuesAssigned(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 9;

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/result.json', json_encode([
    'scheduled_calories' => $scheduled_calories,
    'total_calories' => $total_calories,
    'current_goal' => $current_goal,
    'today' => $today,
    'today_date' => $today_date,
    'tip' => $tip,
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner_get.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner_get.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $resultFile = $sandbox . '/result.json';
        $this->assertFileExists($resultFile);

        $data = json_decode((string) file_get_contents($resultFile), true);
        $this->assertIsArray($data);

        $this->assertSame(1800, $data['scheduled_calories']);
        $this->assertSame(1450, $data['total_calories']);
        $this->assertSame(2000, $data['current_goal']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['today_date']);
        $this->assertNotSame('', $data['today']);

        $scheduledLog = json_decode((string) file_get_contents($sandbox . '/getScheduledCalories_log.json'), true);
        $checkLog = json_decode((string) file_get_contents($sandbox . '/checkCalories_log.json'), true);
        $totalLog = json_decode((string) file_get_contents($sandbox . '/getTotalCalories_log.json'), true);
        $goalLog = json_decode((string) file_get_contents($sandbox . '/getDailyGoal_log.json'), true);

        $this->assertSame(['fake-conn', 9, $data['today']], $scheduledLog);
        $this->assertSame(['fake-conn', 9, $data['today_date'], 1800], $checkLog);
        $this->assertSame(['fake-conn', 9, $data['today_date']], $totalLog);
        $this->assertSame(['fake-conn', 9], $goalLog);
    }

    public function testTipIsSanitizedByRemovingCodeFences(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 4;

include __DIR__ . '/app/views/subject.php';

file_put_contents(__DIR__ . '/tip.txt', $tip);
PHP;

        file_put_contents($sandbox . '/runner_tip.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner_tip.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $tipFile = $sandbox . '/tip.txt';
        $this->assertFileExists($tipFile);

        $tip = (string) file_get_contents($tipFile);
        $this->assertSame('Stay balanced and drink water.', $tip);
        $this->assertStringNotContainsString('```', $tip);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/calories_test_' . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);
        mkdir($dir . '/config', 0777, true);
        mkdir($dir . '/app/views', 0777, true);
        mkdir($dir . '/app/models', 0777, true);

        copy($this->sourceFile, $dir . '/app/views/subject.php');

        file_put_contents($dir . '/config/api_config.php', "<?php\n");
        file_put_contents($dir . '/config/login_page_config.php', <<<'PHP'
<?php

class FakeConn
{
    public string $marker = 'fake-conn';
}

$conn = new FakeConn();
PHP);

        file_put_contents($dir . '/app/models/sql_calorie_functions.php', <<<'PHP'
<?php

function addCalories($conn, $userId, $calories, $todayDate): void
{
    file_put_contents(
        __DIR__ . '/../../addCalories_log.json',
        json_encode([$conn->marker, $userId, $calories, $todayDate], JSON_PRETTY_PRINT)
    );
}

function removeCalories($conn, $userId, $calories, $todayDate): void
{
    file_put_contents(
        __DIR__ . '/../../removeCalories_log.json',
        json_encode([$conn->marker, $userId, $calories, $todayDate], JSON_PRETTY_PRINT)
    );
}

function getScheduledCalories($conn, $userId, $today)
{
    file_put_contents(
        __DIR__ . '/../../getScheduledCalories_log.json',
        json_encode([$conn->marker, $userId, $today], JSON_PRETTY_PRINT)
    );

    return 1800;
}

function checkCalories($conn, $userId, $todayDate, $scheduledCalories): void
{
    file_put_contents(
        __DIR__ . '/../../checkCalories_log.json',
        json_encode([$conn->marker, $userId, $todayDate, $scheduledCalories], JSON_PRETTY_PRINT)
    );
}

function getTotalCalories($conn, $userId, $todayDate)
{
    file_put_contents(
        __DIR__ . '/../../getTotalCalories_log.json',
        json_encode([$conn->marker, $userId, $todayDate], JSON_PRETTY_PRINT)
    );

    return 1450;
}

function getDailyGoal($conn, $userId)
{
    file_put_contents(
        __DIR__ . '/../../getDailyGoal_log.json',
        json_encode([$conn->marker, $userId], JSON_PRETTY_PRINT)
    );

    return 2000;
}
PHP);

        file_put_contents($dir . '/app/models/api_calorie_functions.php', <<<'PHP'
<?php

function getCalorieTip($totalCalories, $currentGoal)
{
    return [
        'content' => [
            [
                'text' => "```json\nStay balanced and drink water.\n```"
            ]
        ]
    ];
}
PHP);

        return $dir;
    }
}
