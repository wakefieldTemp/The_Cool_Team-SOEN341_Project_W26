<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SearchRecipeTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../Sprint Planning/Code/recipe_creation.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real search page path.');
    }

    public function testSearchDisplaysMatchingRecipe(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 10;
$_GET['q'] = 'pasta';

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

        $this->assertStringContainsString('Creamy Pasta', $html);
        $this->assertStringNotContainsString('Chicken Soup', $html);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/search_recipe_test_' . bin2hex(random_bytes(6));
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
        // Simulate a search result for "pasta"
        if (stripos($this->sql, 'FROM recipes') !== false) {
            return new FakeResultSet([
                [
                    'recipe_id' => 1,
                    'recipe_name' => 'Creamy Pasta',
                    'description' => 'A pasta dish',
                ]
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
        file_put_contents($dir . '/sql_recipe_functions.php', "<?php\n");

        return $dir;
    }
}
