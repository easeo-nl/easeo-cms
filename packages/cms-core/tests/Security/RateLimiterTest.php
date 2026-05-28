<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Security;

use Easeo\Cms\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/rate-limiter-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        if (is_dir($this->tmpDataDir)) {
            $this->rmrf($this->tmpDataDir);
        }
    }

    private function rmrf(string $dir): void
    {
        foreach (glob("$dir/*") ?: [] as $f) {
            is_dir($f) ? $this->rmrf($f) : unlink($f);
        }
        rmdir($dir);
    }

    public function test_new_ip_is_not_limited(): void
    {
        $rl = new RateLimiter(maxAttempts: 5, windowSeconds: 900, context: 'test-fresh');
        $this->assertFalse($rl->isLimited('192.0.2.1'));
    }

    public function test_hits_accumulate_until_limit(): void
    {
        $rl = new RateLimiter(maxAttempts: 3, windowSeconds: 900, context: 'test-accumulate');
        $ip = '192.0.2.2';
        $this->assertFalse($rl->isLimited($ip));
        $rl->hit($ip);
        $rl->hit($ip);
        $this->assertFalse($rl->isLimited($ip), '2 hits should not exceed limit of 3');
        $rl->hit($ip);
        $this->assertTrue($rl->isLimited($ip), '3 hits should hit limit of 3');
    }

    public function test_reset_clears_counter(): void
    {
        $rl = new RateLimiter(maxAttempts: 2, windowSeconds: 900, context: 'test-reset');
        $ip = '192.0.2.3';
        $rl->hit($ip);
        $rl->hit($ip);
        $this->assertTrue($rl->isLimited($ip));
        $rl->reset($ip);
        $this->assertFalse($rl->isLimited($ip));
    }

    public function test_different_ips_are_independent(): void
    {
        $rl = new RateLimiter(maxAttempts: 2, windowSeconds: 900, context: 'test-isolation');
        $rl->hit('192.0.2.4');
        $rl->hit('192.0.2.4');
        $this->assertTrue($rl->isLimited('192.0.2.4'));
        $this->assertFalse($rl->isLimited('192.0.2.5'), 'a different IP should have its own counter');
    }

    public function test_different_contexts_are_independent(): void
    {
        $rlA = new RateLimiter(maxAttempts: 2, windowSeconds: 900, context: 'context-a');
        $rlB = new RateLimiter(maxAttempts: 2, windowSeconds: 900, context: 'context-b');
        $ip = '192.0.2.6';
        $rlA->hit($ip);
        $rlA->hit($ip);
        $this->assertTrue($rlA->isLimited($ip));
        $this->assertFalse($rlB->isLimited($ip), 'a different context should have its own counter');
    }

    public function test_storage_dir_lives_inside_data_dir(): void
    {
        new RateLimiter(maxAttempts: 5, windowSeconds: 900, context: 'storage-check');
        $this->assertDirectoryExists("{$this->tmpDataDir}/rate_limits/storage-check");
    }
}
