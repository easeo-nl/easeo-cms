<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Migration;

use Easeo\Cms\Migration\Runner;
use Easeo\Cms\Migration\SchemaVersion;
use PHPUnit\Framework\TestCase;

final class IntegrationTest extends TestCase
{
    private string $tmpDataDir;
    private string $migrationsDir;
    /** @var string|false */
    private $savedEasoData;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/mig-int-' . uniqid('', true);
        $this->migrationsDir = sys_get_temp_dir() . '/mig-int-migs-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        mkdir($this->migrationsDir, 0755, true);
        $this->savedEasoData = getenv('EASEO_DATA');
        putenv("EASEO_DATA={$this->tmpDataDir}");

        // Bootstrap a minimal site.json so audit log resolves dataPath
        file_put_contents($this->tmpDataDir . '/site.json', '{}');
    }

    protected function tearDown(): void
    {
        if ($this->savedEasoData === false) {
            putenv('EASEO_DATA');
        } else {
            putenv("EASEO_DATA={$this->savedEasoData}");
        }
        foreach ([$this->tmpDataDir, $this->migrationsDir] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $this->removeDir($dir);
        }
    }

    public function test_runner_applies_pending_migration_and_writes_audit_entry(): void
    {
        // Drop a fixture migration into the test migrations dir
        copy(
            __DIR__ . '/Fixtures/0099_audit_log_check.php',
            $this->migrationsDir . '/0099_audit_log_check.php'
        );

        $runner = new Runner($this->tmpDataDir, $this->migrationsDir);
        $applied = $runner->runPending();

        $this->assertSame([99], $applied);

        $version = new SchemaVersion($this->tmpDataDir);
        $this->assertSame(99, $version->current());

        // Audit log entry exists
        $auditFile = $this->tmpDataDir . '/audit.log';
        $this->assertFileExists($auditFile);
        $auditContent = (string) file_get_contents($auditFile);
        $this->assertStringContainsString('migration_applied', $auditContent);
        $this->assertStringContainsString('v99', $auditContent);
        $this->assertStringContainsString('Audit-log smoke', $auditContent);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($entries as $entry) {
            $path = "$dir/$entry";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test_runner_is_idempotent_skips_already_applied(): void
    {
        copy(
            __DIR__ . '/Fixtures/0099_audit_log_check.php',
            $this->migrationsDir . '/0099_audit_log_check.php'
        );
        $runner = new Runner($this->tmpDataDir, $this->migrationsDir);
        $runner->runPending(); // first apply
        $applied = $runner->runPending(); // second run — nothing pending
        $this->assertSame([], $applied);
    }
}
