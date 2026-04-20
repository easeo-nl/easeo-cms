<?php
namespace Easeo\Tools\Tests;

use Easeo\Tools\RenameEngine;
use PHPUnit\Framework\TestCase;

class RenameEngineTest extends TestCase {
    private string $tmpDir;

    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . '/rename-test-' . uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir . '/legacy');
        mkdir($this->tmpDir . '/callers');
        copy(__DIR__ . '/fixtures/sample-engine.php', $this->tmpDir . '/legacy/sample.php');
        copy(__DIR__ . '/fixtures/sample-caller.php', $this->tmpDir . '/callers/caller.php');
    }

    protected function tearDown(): void {
        shell_exec('rm -rf ' . escapeshellarg($this->tmpDir));
    }

    public function testConverteertFunctiesNaarStaticClassMethods(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => [
                'sample_hello' => 'hello',
                'sample_shout' => 'shout',
            ],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $renamer->rename($mapping, dryRun: false);

        $produced = file_get_contents($this->tmpDir . '/src/Sample/Greeter.php');
        $this->assertStringContainsString('namespace Easeo\\Cms\\Sample;', $produced);
        $this->assertStringContainsString('class Greeter', $produced);
        $this->assertMatchesRegularExpression('/public static function hello\(string \$name\)\s*:\s*string/', $produced);
        $this->assertMatchesRegularExpression('/public static function shout\(string \$text\)\s*:\s*string/', $produced);
    }

    public function testVervangtCallSitesInCallers(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => ['sample_hello' => 'hello', 'sample_shout' => 'shout'],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $renamer->rename($mapping, dryRun: false);

        $caller = file_get_contents($this->tmpDir . '/callers/caller.php');
        $this->assertStringContainsString('Greeter::hello(\'wereld\')', $caller);
        $this->assertStringContainsString('Greeter::shout(\'hi\')', $caller);
        $this->assertStringContainsString('use Easeo\\Cms\\Sample\\Greeter;', $caller);
    }

    public function testDryRunVeranderDirectoryNiet(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => ['sample_hello' => 'hello', 'sample_shout' => 'shout'],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $diff = $renamer->rename($mapping, dryRun: true);

        $this->assertFileDoesNotExist($this->tmpDir . '/src/Sample/Greeter.php');
        $this->assertFileExists($this->tmpDir . '/legacy/sample.php');
        $this->assertNotEmpty($diff);
    }
}
