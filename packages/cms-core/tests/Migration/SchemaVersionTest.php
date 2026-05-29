<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Migration;

use Easeo\Cms\Migration\SchemaVersion;
use PHPUnit\Framework\TestCase;

final class SchemaVersionTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/schema-version-test-' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function test_returns_zero_when_file_missing(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $this->assertSame(0, $sv->current());
    }

    public function test_set_and_get_round_trip(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(42);
        $this->assertSame(42, $sv->current());
    }

    public function test_atomic_write_via_tmp_file(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(7);

        // The final file must exist and contain the version
        $this->assertFileExists($this->tmpDir . '/.schema-version');
        $this->assertSame('7', trim((string) file_get_contents($this->tmpDir . '/.schema-version')));

        // No leftover .tmp file
        $this->assertFileDoesNotExist($this->tmpDir . '/.schema-version.tmp');
    }

    public function test_returns_zero_for_non_numeric_content(): void
    {
        file_put_contents($this->tmpDir . '/.schema-version', 'corrupted');
        $sv = new SchemaVersion($this->tmpDir);
        $this->assertSame(0, $sv->current());
    }

    public function test_set_overwrites_previous_version(): void
    {
        $sv = new SchemaVersion($this->tmpDir);
        $sv->set(1);
        $sv->set(5);
        $this->assertSame(5, $sv->current());
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
