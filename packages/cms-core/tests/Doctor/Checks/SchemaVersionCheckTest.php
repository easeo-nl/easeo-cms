<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Doctor\Checks;

use Easeo\Cms\Doctor\CheckStatus;
use Easeo\Cms\Doctor\Checks\SchemaVersionCheck;
use Easeo\Cms\Migration\SchemaVersion;
use PHPUnit\Framework\TestCase;

final class SchemaVersionCheckTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/easeo-doctor-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testMissingDataDirReturnsSkipped(): void
    {
        // tmpDir exists but no data/ subdir
        $check  = new SchemaVersionCheck($this->tmpDir);
        $result = $check->run();

        $this->assertSame(CheckStatus::Skipped, $result->status);
        $this->assertStringContainsString('data/', $result->detail);
    }

    public function testExistingDataDirReturnsOkWithVersionDetail(): void
    {
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0755);

        // Write schema version 3
        (new SchemaVersion($dataDir))->set(3);

        $check  = new SchemaVersionCheck($this->tmpDir);
        $result = $check->run();

        $this->assertSame(CheckStatus::Ok, $result->status);
        $this->assertStringContainsString('v3', $result->detail);
    }

    public function testVersionComesFromSchemaVersionClass(): void
    {
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0755);

        // No .schema-version file → SchemaVersion returns 0
        $check  = new SchemaVersionCheck($this->tmpDir);
        $result = $check->run();

        $this->assertSame(CheckStatus::Ok, $result->status);
        $this->assertStringContainsString('v0', $result->detail);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->removeDir($p) : unlink($p);
        }
        rmdir($dir);
    }
}
