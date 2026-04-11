<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AllergyScriptTest extends TestCase
{
    private string $sourceFile;

    protected function setUp(): void
    {
        $this->sourceFile = __DIR__ . '/../project/src/controllers/allergy_post.php';
        $this->assertFileExists($this->sourceFile, 'Update $sourceFile to the real allergy script path.');
    }

    public function testAddAllergyCallsAddAllergyWithTrimmedNameAndUserId(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 15;

$_POST['add_allergy'] = '1';
$_POST['allergy_name'] = '  peanuts  ';

include __DIR__ . '/app/controllers/subject.php';

file_put_contents(__DIR__ . '/result.json', json_encode([
    'allergies' => $allergies,
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/addAllergy_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertSame(['peanuts', 15], $args);

        $getLogFile = $sandbox . '/getAllergies_log.json';
        $this->assertFileExists($getLogFile);
        $getArgs = json_decode((string) file_get_contents($getLogFile), true);
        $this->assertSame([15], $getArgs);
    }

    public function testDeleteAllergyCallsDeleteAllergyWithAllergyIdAndUserId(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 22;

$_POST['delete_allergy'] = '1';
$_POST['allergy_id'] = '9';

include __DIR__ . '/app/controllers/subject.php';

file_put_contents(__DIR__ . '/result.json', json_encode([
    'allergies' => $allergies,
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $logFile = $sandbox . '/deleteAllergy_log.json';
        $this->assertFileExists($logFile);

        $args = json_decode((string) file_get_contents($logFile), true);
        $this->assertSame(['9', 22], $args);

        $getLogFile = $sandbox . '/getAllergies_log.json';
        $this->assertFileExists($getLogFile);
        $getArgs = json_decode((string) file_get_contents($getLogFile), true);
        $this->assertSame([22], $getArgs);
    }

    public function testGetAllergiesAssignsReturnedValueToAllergiesVariable(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 31;

include __DIR__ . '/app/controllers/subject.php';

file_put_contents(__DIR__ . '/result.json', json_encode([
    'allergies' => $allergies,
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $resultFile = $sandbox . '/result.json';
        $this->assertFileExists($resultFile);

        $data = json_decode((string) file_get_contents($resultFile), true);
        $this->assertIsArray($data);
        $this->assertSame([
            ['allergy_id' => 1, 'allergy_name' => 'peanuts'],
            ['allergy_id' => 2, 'allergy_name' => 'soy'],
        ], $data['allergies']);
    }

    public function testNoAddOrDeleteWhenNoPostFlagsAreSet(): void
    {
        $sandbox = $this->makeSandbox();

        $runner = <<<'PHP'
<?php
error_reporting(E_ERROR | E_PARSE);

session_start();
$_SESSION['user_id'] = 44;

include __DIR__ . '/app/controllers/subject.php';

file_put_contents(__DIR__ . '/result.json', json_encode([
    'allergies' => $allergies,
], JSON_PRETTY_PRINT));
PHP;

        file_put_contents($sandbox . '/runner.php', $runner);

        exec(PHP_BINARY . ' ' . escapeshellarg($sandbox . '/runner.php'), $output, $exitCode);
        $this->assertSame(0, $exitCode, implode("\n", $output));

        $this->assertFileDoesNotExist($sandbox . '/addAllergy_log.json');
        $this->assertFileDoesNotExist($sandbox . '/deleteAllergy_log.json');

        $getLogFile = $sandbox . '/getAllergies_log.json';
        $this->assertFileExists($getLogFile);
        $getArgs = json_decode((string) file_get_contents($getLogFile), true);
        $this->assertSame([44], $getArgs);
    }

    private function makeSandbox(): string
    {
        $dir = sys_get_temp_dir() . '/allergy_test_' . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);
        mkdir($dir . '/config', 0777, true);
        mkdir($dir . '/app/controllers', 0777, true);
        mkdir($dir . '/app/models', 0777, true);

        copy($this->sourceFile, $dir . '/app/controllers/subject.php');

        file_put_contents($dir . '/config/login_page_config.php', "<?php\n");

        file_put_contents($dir . '/app/models/sql_allergy_functions.php', <<<'PHP'
<?php

function addAllergy(...$args): void
{
    file_put_contents(__DIR__ . '/../../addAllergy_log.json', json_encode($args, JSON_PRETTY_PRINT));
}

function deleteAllergy(...$args): void
{
    file_put_contents(__DIR__ . '/../../deleteAllergy_log.json', json_encode($args, JSON_PRETTY_PRINT));
}

function getAllergies(...$args): array
{
    file_put_contents(__DIR__ . '/../../getAllergies_log.json', json_encode($args, JSON_PRETTY_PRINT));

    return [
        ['allergy_id' => 1, 'allergy_name' => 'peanuts'],
        ['allergy_id' => 2, 'allergy_name' => 'soy'],
    ];
}
PHP);

        return $dir;
    }
}
