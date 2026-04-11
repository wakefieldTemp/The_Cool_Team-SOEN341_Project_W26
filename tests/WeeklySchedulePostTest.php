<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WeeklySchedulePostTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/controllers/main_menu_post.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real script path.');
    }

    public function testAddActionCallsAddMealToScheduleWithExpectedArguments(): void
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
$_POST['day_of_week'] = 'Monday';
$_POST['meal_type'] = 'Dinner';

$userId = 14;
$schedule_id = 999;

include __DIR__ . '/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/add_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertSame(['fake_conn', 14, 25, 'Monday', 'Dinner'], $args);

        // script should exit before reaching the end of runner.php
        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    public function testDeleteActionCallsDeleteMealFromScheduleWithExpectedArguments(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 31;

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'delete';

$userId = 31;
$schedule_id = 77;

include __DIR__ . '/subject.php';
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/delete_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertSame(['fake_conn', 31, 77], $args);

        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    public function testNoActionMeansNoScheduleFunctionCalled(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 50;

$_SERVER['REQUEST_METHOD'] = 'GET';

$userId = 50;
$schedule_id = 10;

include __DIR__ . '/subject.php';

file_put_contents(__DIR__ . '/finished.txt', 'done');
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $this->assertFileExists($sandbox . '/finished.txt');
        $this->assertFileDoesNotExist($sandbox . '/add_log.json');
        $this->assertFileDoesNotExist($sandbox . '/delete_log.json');
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/weekly_schedule_test_' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        copy($this->sourceFile, $dir . '/subject.php');

        file_put_contents($dir . '/bootstrap.php', <<<'PHP'
<?php

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://example.com');
}

$conn = 'fake_conn';

function addMealToSchedule(...$args): void
{
    file_put_contents(__DIR__ . '/add_log.json', json_encode($args, JSON_PRETTY_PRINT));
}

function deleteMealFromSchedule(...$args): void
{
    file_put_contents(__DIR__ . '/delete_log.json', json_encode($args, JSON_PRETTY_PRINT));
}
PHP);

        // prepend bootstrap include to subject copy
        $original = (string) file_get_contents($dir . '/subject.php');
        file_put_contents($dir . '/subject.php', "<?php\nrequire_once __DIR__ . '/bootstrap.php';\n?>" . substr($original, 5));

        return $dir;
    }
}
