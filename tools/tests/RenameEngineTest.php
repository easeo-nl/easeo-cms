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

    public function testPreservesDeclareAndNamespaceOrder(): void {
        $caller = $this->tmpDir . '/callers/with-namespace.php';
        file_put_contents($caller, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Handler;

echo sample_hello('wereld');
PHP
        );

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

        $content = file_get_contents($caller);
        // Verify declare is still first, namespace second, use comes AFTER namespace declaration
        $this->assertMatchesRegularExpression(
            '/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;.*namespace\s+App.*use\s+Easeo/s',
            $content,
            'declare moet voor namespace, namespace voor use'
        );

        // File must parse without syntax errors
        $parser = (new \PhpParser\ParserFactory())->create(\PhpParser\ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($content);
        $this->assertNotNull($ast, 'Resulterende PHP moet geldig parsen');
    }

    public function testLaatFullyQualifiedCallsOngemoeid(): void {
        file_put_contents($this->tmpDir . '/callers/fq-caller.php', <<<'PHP'
<?php
echo \sample_hello('fqn');
echo sample_shout('normal');
PHP
        );

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

        $content = file_get_contents($this->tmpDir . '/callers/fq-caller.php');
        // FQ call MUST remain unchanged
        $this->assertMatchesRegularExpression('/\\\\sample_hello\s*\(/', $content, '\\sample_hello moet niet omgezet worden');
        // Unqualified call SHOULD be rewritten
        $this->assertStringContainsString('Greeter::shout', $content);
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
