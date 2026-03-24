<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DeleteRecipeTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../Sprint Planning/Code/recipe_creation.php';
        $this->assertFileExists($this->sourceFile, 'Check your path.');
    }

    public function testRedirectsWhenRecipeIdIsMissing(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 15;

ob_start();
include __DIR__ . '/subject.php';
ob_get_clean();

file_put_contents(__DIR__ . '/finished.txt', 'reached-end');
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        // Script should exit early → runner should NOT continue
        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
        $this->assertFileDoesNotExist($sandbox . '/deleteRecipe_log.json');
    }

    public function testDeletesRecipeWithGetRecipeId(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 7;
$_GET['recipe_id'] = 42;

ob_start();
include __DIR__ . '/subject.php';
ob_get_clean();

file_put_contents(__DIR__ . '/finished.txt', 'reached-end');
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/deleteRecipe_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);

        $this->assertSame(7, $args[0]);
        $this->assertSame(42, $args[1]);

        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    public function testDeletesRecipeWithPostRecipeId(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 21;
$_POST['recipe_id'] = 88;

ob_start();
include __DIR__ . '/subject.php';
ob_get_clean();

file_put_contents(__DIR__ . '/finished.txt', 'reached-end');
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);
        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/deleteRecipe_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);

        $this->assertSame(21, $args[0]);
        $this->assertSame(88, $args[1]);

        $this->assertFileDoesNotExist($sandbox . '/finished.txt');
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/delete_recipe_test_' . bin2hex(random_bytes(6));
        mkdir($dir, 0777, true);

        copy($this->sourceFile, $dir . '/subject.php');

        // ✅ FIX: stub api_config.php
        file_put_contents($dir . '/api_config.php', "<?php\n");

        // existing stubs
        file_put_contents($dir . '/login_page_config.php', "<?php\n");

        file_put_contents($dir . '/sql_recipe_functions.php', <<<'PHP'
<?php

function deleteRecipe(...$args): void
{
    file_put_contents(__DIR__ . '/deleteRecipe_log.json', json_encode($args));
}
PHP);

        return $dir;
    }
}
