<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use PHPUnit\Framework\TestCase;

final class CheckChangelogTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'changelog');
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpFile);
    }

    public function test_passes_when_changelog_has_entry_for_version(): void
    {
        file_put_contents($this->tmpFile, "# Changelog\n\n## [1.2.3] - 2026-05-23\n\n### Added\n- Foo\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile 1.2.3 2>&1", $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }

    public function test_fails_when_changelog_has_no_entry_for_version(): void
    {
        file_put_contents($this->tmpFile, "# Changelog\n\n## [1.2.3] - 2026-05-23\n\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile 9.9.9 2>&1", $output, $status);
        $this->assertNotSame(0, $status);
        $this->assertStringContainsString('9.9.9', implode("\n", $output));
    }

    public function test_strips_v_prefix_from_tag(): void
    {
        file_put_contents($this->tmpFile, "## [1.2.3] - 2026-05-23\n### Added\n- x\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile v1.2.3 2>&1", $output, $status);
        $this->assertSame(0, $status);
    }
}
