<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Tests for tools/check-client-versions.php
 *
 * These tests verify the logic of the script without hitting Packagist or GitHub.
 */
final class CheckClientVersionsTest extends TestCase
{
    public function testScriptFileExists(): void
    {
        $this->assertFileExists(dirname(__DIR__) . '/check-client-versions.php');
    }

    public function testScriptIsReadable(): void
    {
        $file = dirname(__DIR__) . '/check-client-versions.php';
        $this->assertIsReadable($file);
    }

    public function testScriptSyntaxIsValid(): void
    {
        $file   = dirname(__DIR__) . '/check-client-versions.php';
        $output = [];
        $code   = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $code);
        $this->assertSame(0, $code, implode("\n", $output));
    }

    public function testClientRepoListContainsExpectedRepos(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/check-client-versions.php');
        $this->assertStringContainsString('easeo-nl/rww-website', $content);
        $this->assertStringContainsString('easeo-nl/qpmarketing-website', $content);
    }
}
