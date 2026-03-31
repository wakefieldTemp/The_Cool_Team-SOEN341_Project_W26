<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DeleteRecipeTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/views/recipes.php';
        $this->assertFileExists($this->sourceFile, 'Check your path to recipes.php.');
    }

    public function testDeleteRecipePreparesCorrectDeleteQuery(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 12;

$_POST['delete_recipe'] = '1';
$_POST['recipe_id'] = 44;

ob_start();
include __DIR__ . '/subject.php';
ob_end_clean();
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $sqlFile = $sandbox . '/delete_sql.txt';
        $this->assertFileExists($sqlFile);

        $sql = (string) file_get_contents($sqlFile);
        $this->assertSame(
            'DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?',
            trim($sql)
        );
    }

    public function testDeleteRecipeBindsRecipeIdAndUserIdAsIntegers(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 99;

$_POST['delete_recipe'] = '1';
$_POST['recipe_id'] = 123;

ob_start();
include __DIR__ . '/subject.php';
ob_end_clean();
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $bindFile = $sandbox . '/delete_bind.json';
        $this->assertFileExists($bindFile);

        $bind = json_decode((string) file_get_contents($bindFile), true);
        $this->assertIsArray($bind);

        $this->assertSame('ii', $bind['types']);
        $this->assertSame([123, 99], $bind['params']);
    }

    public function testDeleteRecipeExecutesDeleteStatement(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 7;

$_POST['delete_recipe'] = '1';
$_POST['recipe_id'] = 8;

ob_start();
include __DIR__ . '/subject.php';
ob_end_clean();
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $executedFile = $sandbox . '/delete_executed.txt';
        $this->assertFileExists($executedFile);
        $this->assertSame('executed', trim((string) file_get_contents($executedFile)));
    }

    public function testDeleteIsNotTriggeredWhenDeletePostFlagIsMissing(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 55;

$_POST['recipe_id'] = 777;

ob_start();
include __DIR__ . '/subject.php';
ob_end_clean();
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));

        $this->assertFileDoesNotExist($sandbox . '/delete_sql.txt');
        $this->assertFileDoesNotExist($sandbox . '/delete_bind.json');
        $this->assertFileDoesNotExist($sandbox . '/delete_executed.txt');
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/delete_recipe_test_' . bin2hex(random_bytes(6));
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

        file_put_contents($dir . '/sql_recipe_functions.php', <<<'PHP'
<?php

function deleteRecipe(...$args): void
{
}

function addRecipe(...$args): void
{
}

function createRecipe(...$args)
{
    return null;
}

function editRecipe(...$args): void
{
}
PHP);

        file_put_contents($dir . '/login_page_config.php', <<<'PHP'
<?php

define('BASE_URL', '');

class FakeResultSet
{
    public function fetch_assoc()
    {
        return null;
    }
}

class FakeStmt
{
    private string $sql;

    public function __construct(string $sql)
    {
        $this->sql = $sql;

        if (strpos($sql, 'DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?') !== false) {
            file_put_contents(__DIR__ . '/delete_sql.txt', $sql);
        }
    }

    public function bind_param($types, &...$params): void
    {
        if (strpos($this->sql, 'DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?') !== false) {
            file_put_contents(
                __DIR__ . '/delete_bind.json',
                json_encode([
                    'types' => $types,
                    'params' => $params,
                ], JSON_PRETTY_PRINT)
            );
        }
    }

    public function execute(): void
    {
        if (strpos($this->sql, 'DELETE FROM recipes WHERE recipe_id = ? AND user_id = ?') !== false) {
            file_put_contents(__DIR__ . '/delete_executed.txt', 'executed');
        }
    }

    public function get_result()
    {
        return new FakeResultSet();
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
PHP);

        return $dir;
    }
}