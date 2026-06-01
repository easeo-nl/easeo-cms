<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Migration;

use Easeo\Cms\Migration\VersionTracker;
use PHPUnit\Framework\TestCase;

final class VersionTrackerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vt-test-' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $f) {
            is_file($f) && unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    public function test_last_seen_returns_null_when_no_file(): void
    {
        $tracker = new VersionTracker($this->tmpDir);
        $this->assertNull($tracker->lastSeen());
    }

    public function test_record_then_last_seen_round_trip(): void
    {
        $tracker = new VersionTracker($this->tmpDir);
        $tracker->record('1.2.3');
        $this->assertSame('1.2.3', $tracker->lastSeen());
    }

    public function test_has_changed_returns_false_when_never_recorded(): void
    {
        // First deploy edge case: no marker file yet — treat as "no change"
        // so we don't pointlessly reset OPcache on every fresh install boot.
        $tracker = new VersionTracker($this->tmpDir);
        $this->assertFalse($tracker->hasChanged('1.0.0'));
    }

    public function test_has_changed_returns_true_when_versions_differ(): void
    {
        $tracker = new VersionTracker($this->tmpDir);
        $tracker->record('1.0.0');
        $this->assertTrue($tracker->hasChanged('2.0.0'));
    }

    public function test_has_changed_returns_false_when_versions_match(): void
    {
        $tracker = new VersionTracker($this->tmpDir);
        $tracker->record('1.0.0');
        $this->assertFalse($tracker->hasChanged('1.0.0'));
    }

    public function test_detect_current_version_returns_dev_monorepo_when_no_installed_json(): void
    {
        $fakeRoot = sys_get_temp_dir() . '/vt-root-' . uniqid('', true);
        mkdir($fakeRoot, 0755, true);
        try {
            $version = VersionTracker::detectCurrentVersion($fakeRoot);
            $this->assertSame('dev-monorepo', $version);
        } finally {
            @rmdir($fakeRoot);
        }
    }

    public function test_detect_current_version_reads_from_installed_json(): void
    {
        $fakeRoot = sys_get_temp_dir() . '/vt-root-installed-' . uniqid('', true);
        $composerDir = $fakeRoot . '/vendor/composer';
        mkdir($composerDir, 0755, true);

        $installed = [
            'packages' => [
                ['name' => 'some/other-package', 'version' => '3.0.0'],
                ['name' => 'easeo/cms-core', 'version' => '0.2.1'],
            ],
        ];
        file_put_contents($composerDir . '/installed.json', json_encode($installed));

        try {
            $version = VersionTracker::detectCurrentVersion($fakeRoot);
            $this->assertSame('0.2.1', $version);
        } finally {
            unlink($composerDir . '/installed.json');
            rmdir($composerDir);
            rmdir($fakeRoot . '/vendor');
            rmdir($fakeRoot);
        }
    }
}
