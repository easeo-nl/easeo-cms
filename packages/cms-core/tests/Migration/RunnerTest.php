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

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/runner-test-' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
        $this->fixturesDir = __DIR__ . '/Fixtures';
    }

    protected function tearDown(): void
    {
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

        // After running both fixtures (versions 10 and 20), schema version is 20
        $this->assertSame(20, $sv->current());
    }

    public function test_migrations_applied_in_version_order(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(0);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $applied = $runner->runPending();

        // Must be applied in ascending version order
        $this->assertSame([10, 20], $applied);
    }

    public function test_already_applied_migrations_not_rerun(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(10);

        $runner = new Runner($this->tmpDir, $this->fixturesDir);
        $applied = $runner->runPending();

        // Only version 20 should be applied (10 already done)
        $this->assertSame([20], $applied);
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
