<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Routes;

use Easeo\Cms\Routes\Healthcheck;
use PHPUnit\Framework\TestCase;

final class HealthcheckTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/easeo-hc-test-' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        // Ensure HEALTHCHECK_SECRET is not set between tests
        putenv('HEALTHCHECK_SECRET');
        unset($_ENV['HEALTHCHECK_SECRET'], $_SERVER['HEALTHCHECK_SECRET']);
    }

    protected function tearDown(): void
    {
        putenv('HEALTHCHECK_SECRET');
        unset($_ENV['HEALTHCHECK_SECRET'], $_SERVER['HEALTHCHECK_SECRET']);
        $this->removeDir($this->tmpDir);
    }

    public function testBasicOkResponseWithoutSecret(): void
    {
        $payload = Healthcheck::handle($this->tmpDir, null);
        $this->assertSame(['status' => 'ok'], $payload);
    }

    public function testWrongSecretReturnsBasicOnly(): void
    {
        putenv('HEALTHCHECK_SECRET=correct-secret');
        $_ENV['HEALTHCHECK_SECRET']    = 'correct-secret';
        $_SERVER['HEALTHCHECK_SECRET'] = 'correct-secret';

        $payload = Healthcheck::handle($this->tmpDir, 'wrong-secret');
        $this->assertSame(['status' => 'ok'], $payload);
        $this->assertArrayNotHasKey('schema_version', $payload);
    }

    public function testCorrectSecretReturnsExtendedPayload(): void
    {
        putenv('HEALTHCHECK_SECRET=my-secret');
        $_ENV['HEALTHCHECK_SECRET']    = 'my-secret';
        $_SERVER['HEALTHCHECK_SECRET'] = 'my-secret';

        $payload = Healthcheck::handle($this->tmpDir, 'my-secret');

        $this->assertSame('ok', $payload['status']);
        $this->assertArrayHasKey('cms_core_version', $payload);
        $this->assertArrayHasKey('php_version', $payload);
        $this->assertArrayHasKey('timestamp', $payload);
        $this->assertArrayHasKey('data_writable', $payload);
    }

    public function testExtendedPayloadContainsSchemaVersion(): void
    {
        putenv('HEALTHCHECK_SECRET=secret123');
        $_ENV['HEALTHCHECK_SECRET']    = 'secret123';
        $_SERVER['HEALTHCHECK_SECRET'] = 'secret123';

        // Create data dir so schema_version is not null
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0755);

        $payload = Healthcheck::handle($this->tmpDir, 'secret123');

        $this->assertArrayHasKey('schema_version', $payload);
        $this->assertIsInt($payload['schema_version']);
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
