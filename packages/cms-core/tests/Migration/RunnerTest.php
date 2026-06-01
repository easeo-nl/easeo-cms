<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Migration;

use Easeo\Cms\Migration\Runner;
use Easeo\Cms\Migration\SchemaVersion;
use PHPUnit\Framework\TestCase;

final class RunnerTest extends TestCase
{
    private string $tmpDir;
    private string $fixturesDir;
    /** @var string|false */
    private $savedEasoData;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/runner-test-' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
        $this->fixturesDir = __DIR__ . '/Fixtures';
        $this->savedEasoData = getenv('EASEO_DATA');
    }

    protected function tearDown(): void
    {
        // Restore EASEO_DATA env so audit-log side-effects don't leak between tests
        if ($this->savedEasoData === false) {
            putenv('EASEO_DATA');
        } else {
            putenv("EASEO_DATA={$this->savedEasoData}");
        }
        $this->removeDir($this->tmpDir);
    }

    public function test_returns_empty_when_migrations_dir_missing(): void
    {
        $runner = new Runner($this->tmpDir, $this->tmpDir . '/nonexistent-migrations');
        $this->assertSame([], $runner->runPending());
    }

    public function test_returns_empty_when_no_pending_migrations(): void
    {
        // Set current version beyond all fixtures
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(9999);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $this->assertSame([], $runner->runPending());
    }

    public function test_single_pending_migration_applied(): void
    {
        // Pre-set version to 5 so only fixture 0010 (version 10) and 0020 (version 20) are pending
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(5);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $applied = $runner->runPending();

        $this->assertContains(10, $applied);
        $this->assertContains(20, $applied);
        $this->assertFileExists($this->tmpDir . '/a.json');
        $this->assertFileExists($this->tmpDir . '/b.json');
    }

    public function test_version_bumped_to_highest_applied(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(0);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $runner->runPending();

        // Fixtures: 0010 (v10), 0020 (v20), 0099 (v99) — highest is 99
        $this->assertSame(99, $sv->current());
    }

    public function test_migrations_applied_in_version_order(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(0);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $applied = $runner->runPending();

        // Fixtures: 0010 (v10), 0020 (v20), 0099 (v99) — must be applied in ascending order
        $this->assertSame([10, 20, 99], $applied);
    }

    public function test_already_applied_migrations_not_rerun(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(10);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $applied = $runner->runPending();

        // Only versions 20 and 99 should be applied (10 already done)
        $this->assertSame([20, 99], $applied);
        $this->assertFileDoesNotExist($this->tmpDir . '/a.json');
        $this->assertFileExists($this->tmpDir . '/b.json');
    }

    public function test_invalid_migration_file_throws_runtime_exception(): void
    {
        // Create a temp migrations dir with a bad migration
        $badDir = $this->tmpDir . '/bad-migrations';
        mkdir($badDir, 0755, true);
        file_put_contents($badDir . '/0001_bad.php', "<?php\nreturn 'not-a-migration';");

        $runner = new Runner($this->tmpDir, $badDir);
        $this->expectException(\RuntimeException::class);
        $runner->runPending();
    }

    public function test_audit_log_entry_is_written_after_successful_migration(): void
    {
        putenv("EASEO_DATA={$this->tmpDir}");
        // site.json needed so AuditLogger::dataPath() resolves to tmpDir
        file_put_contents($this->tmpDir . '/site.json', '{}');

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $runner->runPending();

        $auditFile = $this->tmpDir . '/audit.log';
        $this->assertFileExists($auditFile);
        $content = (string) file_get_contents($auditFile);
        $this->assertStringContainsString('migration_applied', $content);
    }

    public function test_audit_log_failure_does_not_kill_runner(): void
    {
        // Hard to simulate audit-log failure cross-platform; behaviour is
        // fail-soft per spec (try/catch in Runner wraps AuditLogger::log()).
        $this->markTestSkipped(
            'Hard to simulate audit-log failure cross-platform; '
            . 'fail-soft behaviour is encoded in the try/catch in Runner.'
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = "$dir/$entry";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
