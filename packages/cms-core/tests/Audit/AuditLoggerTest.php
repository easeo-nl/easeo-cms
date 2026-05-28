<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Audit;

use Easeo\Cms\Audit\AuditLogger;
use PHPUnit\Framework\TestCase;

final class AuditLoggerTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/audit-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        foreach (glob($this->tmpDataDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDataDir);
    }

    public function test_read_returns_empty_when_no_log(): void
    {
        $this->assertSame([], AuditLogger::read());
    }

    public function test_log_then_read_round_trip(): void
    {
        AuditLogger::log('test-action', 'some details', 'testuser');
        $entries = AuditLogger::read();

        $this->assertCount(1, $entries);
        $this->assertSame('test-action', $entries[0]['actie']);
        $this->assertSame('some details', $entries[0]['details']);
        $this->assertSame('testuser', $entries[0]['gebruiker']);
        $this->assertArrayHasKey('datum', $entries[0]);
        $this->assertArrayHasKey('ip', $entries[0]);
    }

    public function test_read_returns_newest_first(): void
    {
        AuditLogger::log('first', '', 'u');
        AuditLogger::log('second', '', 'u');
        AuditLogger::log('third', '', 'u');

        $entries = AuditLogger::read();
        $this->assertSame('third', $entries[0]['actie']);
        $this->assertSame('first', $entries[count($entries) - 1]['actie']);
    }

    public function test_read_respects_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            AuditLogger::log("action-$i", '', 'u');
        }
        $entries = AuditLogger::read(2);
        $this->assertCount(2, $entries);
    }

    public function test_read_respects_offset(): void
    {
        for ($i = 0; $i < 5; $i++) {
            AuditLogger::log("action-$i", '', 'u');
        }
        $entries = AuditLogger::read(100, 2);
        // newest first: action-4, action-3, action-2, action-1, action-0
        // offset 2 skips action-4, action-3; first returned should be action-2
        $this->assertSame('action-2', $entries[0]['actie']);
        $this->assertCount(3, $entries);
    }

    public function test_log_defaults_user_to_systeem_when_empty(): void
    {
        AuditLogger::log('anon-action');
        $entries = AuditLogger::read();
        $this->assertSame('systeem', $entries[0]['gebruiker']);
    }
}
