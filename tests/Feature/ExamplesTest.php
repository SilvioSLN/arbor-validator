<?php

declare(strict_types=1);

namespace Arbor\Validator\Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExamplesTest extends TestCase
{
    #[DataProvider('exampleFilesProvider')]
    public function testExampleScriptExecutesSuccessfully(string $filename): void
    {
        $filePath = realpath(__DIR__ . '/../../examples/' . $filename);
        $this->assertNotFalse($filePath, "Example file not found: {$filename}");

        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($filePath) . ' 2>&1';
        exec($cmd, $output, $exitCode);

        $outputStr = implode("\n", $output);

        $this->assertSame(
            0,
            $exitCode,
            "Example script {$filename} failed with exit code {$exitCode}.\nOutput:\n{$outputStr}"
        );
        $this->assertStringContainsString('Completed successfully.', $outputStr);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function exampleFilesProvider(): array
    {
        return [
            '01_quickstart_schema'      => ['01_quickstart_schema.php'],
            '02_class_first_dto'        => ['02_class_first_dto.php'],
            '03_brazilian_rules'        => ['03_brazilian_rules.php'],
            '04_uploaded_files'         => ['04_uploaded_files.php'],
            '05_nested_dtos_and_lists'  => ['05_nested_dtos_and_lists.php'],
            '06_error_handling_and_i18n'=> ['06_error_handling_and_i18n.php'],
            '07_request_integration'    => ['07_request_integration.php'],
        ];
    }
}
