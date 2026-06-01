<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use Easeo\Cms\Config\SiteJsonMigrator;
use PHPUnit\Framework\TestCase;

final class MigrateSiteJsonTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/mig-test-' . uniqid('', true);
        mkdir($this->tmp . '/data', 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmp)) {
            $this->rmrf($this->tmp);
        }
    }

    private function rmrf(string $dir): void
    {
        // Use GLOB_BRACE to include dotfiles (e.g. .env written by apply())
        $entries = array_merge(
            glob("$dir/*") ?: [],
            glob("$dir/.*") ?: []
        );
        foreach ($entries as $f) {
            $base = basename($f);
            if ($base === '.' || $base === '..') {
                continue;
            }
            is_dir($f) ? $this->rmrf($f) : unlink($f);
        }
        rmdir($dir);
    }

    private function writeSiteJson(array $data): void
    {
        file_put_contents($this->tmp . '/data/site.json', json_encode($data));
    }

    public function test_plan_returns_empty_when_no_site_json(): void
    {
        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $plan     = $migrator->plan();
        $this->assertFalse($plan['site_json_exists']);
        $this->assertEmpty($plan['secrets_found']);
    }

    public function test_plan_extracts_smtp_secrets(): void
    {
        $this->writeSiteJson([
            'smtp' => [
                'host'       => 'smtp.example.com',
                'port'       => 587,
                'username'   => 'noreply@example.com',
                'encryption' => 'tls',
                // password intentionally omitted for this test (decrypt path tested separately)
            ],
            'company' => ['name' => 'Acme'],
        ]);

        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $plan     = $migrator->plan();

        $this->assertTrue($plan['site_json_exists']);
        $this->assertArrayHasKey('SMTP_HOST', $plan['secrets_found']);
        $this->assertSame('smtp.example.com', $plan['secrets_found']['SMTP_HOST']);
        $this->assertSame('587', $plan['secrets_found']['SMTP_PORT']);
        $this->assertSame('noreply@example.com', $plan['secrets_found']['SMTP_USERNAME']);
        $this->assertSame('tls', $plan['secrets_found']['SMTP_ENCRYPTION']);
    }

    public function test_plan_extracts_gtm_id_from_tracking(): void
    {
        $this->writeSiteJson(['tracking' => ['gtm_id' => 'GTM-ABC123']]);
        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $plan     = $migrator->plan();
        $this->assertSame('GTM-ABC123', $plan['secrets_found']['GTM_ID'] ?? null);
    }

    public function test_plan_skips_empty_secret_values(): void
    {
        $this->writeSiteJson([
            'smtp' => ['host' => 'smtp.example.com', 'username' => ''],
        ]);
        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $plan     = $migrator->plan();
        $this->assertArrayHasKey('SMTP_HOST', $plan['secrets_found']);
        $this->assertArrayNotHasKey('SMTP_USERNAME', $plan['secrets_found']);
    }

    public function test_apply_creates_backup_site_config_and_env(): void
    {
        $this->writeSiteJson([
            'smtp'     => ['host' => 'smtp.x', 'username' => 'u', 'port' => 465],
            'tracking' => ['gtm_id' => 'GTM-123'],
            'company'  => ['name' => 'Acme'],
        ]);

        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $result   = $migrator->apply();

        // Backup exists
        $this->assertFileExists($result['backup_path']);

        // site.config.json contains company, not smtp.host
        $this->assertFileExists($this->tmp . '/data/site.config.json');
        $config = json_decode((string) file_get_contents($this->tmp . '/data/site.config.json'), true);
        $this->assertSame('Acme', $config['company']['name']);
        $this->assertArrayNotHasKey('smtp', $config);
        // tracking with all keys extracted should have no gtm_id remaining
        if (isset($config['tracking'])) {
            $this->assertArrayNotHasKey('gtm_id', $config['tracking']);
        }

        // .env contains commented secrets
        $env = (string) file_get_contents($this->tmp . '/.env');
        $this->assertStringContainsString('# SMTP_HOST="smtp.x"', $env);
        $this->assertStringContainsString('# GTM_ID="GTM-123"', $env);
    }

    public function test_apply_is_idempotent_for_site_config(): void
    {
        $this->writeSiteJson(['company' => ['name' => 'Acme']]);

        $migrator = new SiteJsonMigrator($this->tmp . '/data', $this->tmp);
        $migrator->apply();

        // Second apply must not error
        $second = $migrator->apply();
        $this->assertContains('site.config.json already exists — skipped', $second['actions']);
    }
}
