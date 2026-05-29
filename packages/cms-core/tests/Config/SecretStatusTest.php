<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Config;

use Easeo\Cms\Config\Environment;
use Easeo\Cms\Config\SecretStatus;
use PHPUnit\Framework\TestCase;

class SecretStatusTest extends TestCase
{
    protected function setUp(): void
    {
        Environment::reset();
    }

    protected function tearDown(): void
    {
        Environment::reset();
    }

    public function testIsConfiguredReturnsFalseWhenEnvNotSet(): void
    {
        // Ensure the key is not set
        unset($_ENV['__EASEO_SECRET_TEST__'], $_SERVER['__EASEO_SECRET_TEST__']);
        putenv('__EASEO_SECRET_TEST__');

        $this->assertFalse(SecretStatus::isConfigured('__EASEO_SECRET_TEST__'));
    }

    public function testIsConfiguredReturnsTrueWhenEnvIsSet(): void
    {
        $_ENV['SMTP_HOST'] = 'smtp.example.com';
        $result = SecretStatus::isConfigured('SMTP_HOST');
        unset($_ENV['SMTP_HOST']);
        $this->assertTrue($result);
    }

    public function testSummaryReturnsAllKnownSecrets(): void
    {
        $summary = SecretStatus::summary();
        $this->assertIsArray($summary);
        $this->assertGreaterThanOrEqual(9, count($summary));

        $keys = array_column($summary, 'key');
        $this->assertContains('SMTP_HOST', $keys);
        $this->assertContains('SMTP_PASSWORD', $keys);
        $this->assertContains('MOLLIE_API_KEY', $keys);
        $this->assertContains('GTM_ID', $keys);
    }

    public function testSummaryRowHasExpectedShape(): void
    {
        $summary = SecretStatus::summary();
        $row = $summary[0];

        $this->assertArrayHasKey('key', $row);
        $this->assertArrayHasKey('label', $row);
        $this->assertArrayHasKey('hint', $row);
        $this->assertArrayHasKey('required', $row);
        $this->assertArrayHasKey('configured', $row);
        $this->assertIsBool($row['required']);
        $this->assertIsBool($row['configured']);
    }

    public function testSummaryReflectsConfiguredState(): void
    {
        $_ENV['SMTP_HOST'] = 'smtp.example.com';
        $summary = SecretStatus::summary();
        $row = current(array_filter($summary, fn($r) => $r['key'] === 'SMTP_HOST'));
        unset($_ENV['SMTP_HOST']);

        $this->assertNotFalse($row);
        $this->assertTrue($row['configured']);
    }

    public function testMissingRequiredReturnsEmptyWhenNoRequiredSecrets(): void
    {
        // In current state, all KNOWN_SECRETS have required=false
        $missing = SecretStatus::missingRequired();
        $this->assertSame([], $missing);
    }

    public function testIsConfiguredForSmtpPasswordWithValue(): void
    {
        unset($_ENV['SMTP_PASSWORD'], $_SERVER['SMTP_PASSWORD']);
        putenv('SMTP_PASSWORD');

        $this->assertFalse(SecretStatus::isConfigured('SMTP_PASSWORD'));

        $_ENV['SMTP_PASSWORD'] = 'supersecret';
        $this->assertTrue(SecretStatus::isConfigured('SMTP_PASSWORD'));
        unset($_ENV['SMTP_PASSWORD']);
    }
}
