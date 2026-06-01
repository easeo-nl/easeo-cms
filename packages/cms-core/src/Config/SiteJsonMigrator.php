<?php
declare(strict_types=1);

namespace Easeo\Cms\Config;

/**
 * Splits a legacy site.json into:
 * - site.config.json: branding/content/non-secret keys (kept in data/)
 * - .env template: secret keys (commented out for operator review)
 * - site.json.backup-YYYYMMDD-HHMMSS: backup of original
 *
 * Idempotent: running twice doesn't duplicate state. Plan-output is
 * inspectable in dry-run mode (default); --apply writes files.
 */
final class SiteJsonMigrator
{
    /** Site.json keys whose values become env vars. */
    private const SECRET_PATHS = [
        // Path in site.json => ENV var name
        'smtp.host'        => 'SMTP_HOST',
        'smtp.port'        => 'SMTP_PORT',
        'smtp.username'    => 'SMTP_USERNAME',
        'smtp.password'    => 'SMTP_PASSWORD',
        'smtp.from_email'  => 'SMTP_FROM_EMAIL',
        'smtp.from_name'   => 'SMTP_FROM_NAME',
        'smtp.encryption'  => 'SMTP_ENCRYPTION',
        'tracking.gtm_id'  => 'GTM_ID',
    ];

    public function __construct(
        private readonly string $dataDir,
        private readonly string $appRoot
    ) {}

    /**
     * Plan the migration. Returns a description of what would be done.
     *
     * @return array{
     *     site_json_exists: bool,
     *     site_config_json_exists: bool,
     *     env_exists: bool,
     *     secrets_found: array<string,string>,
     *     site_config_keys: list<string>,
     *     backup_path: string,
     *     env_path: string,
     *     site_config_path: string,
     *     warnings: list<string>
     * }
     */
    public function plan(): array
    {
        $sitePath   = $this->dataDir . '/site.json';
        $configPath = $this->dataDir . '/site.config.json';
        $envPath    = $this->appRoot . '/.env';
        $backupPath = $this->dataDir . '/site.json.backup-' . date('Ymd-His');

        $warnings     = [];
        $secretsFound = [];
        $configKeys   = [];

        if (!is_file($sitePath)) {
            return [
                'site_json_exists'       => false,
                'site_config_json_exists' => is_file($configPath),
                'env_exists'             => is_file($envPath),
                'secrets_found'          => [],
                'site_config_keys'       => [],
                'backup_path'            => $backupPath,
                'env_path'               => $envPath,
                'site_config_path'       => $configPath,
                'warnings'               => ['site.json does not exist — nothing to migrate'],
            ];
        }

        $siteData = json_decode((string) file_get_contents($sitePath), true) ?? [];
        if (!is_array($siteData)) {
            $warnings[] = 'site.json could not be parsed as JSON';
            $siteData   = [];
        }

        // Walk known secret paths
        foreach (self::SECRET_PATHS as $dotKey => $envVar) {
            $value = $this->dotGet($siteData, $dotKey);
            if ($value === null || $value === '') {
                continue;
            }
            // Special handling for legacy-encrypted SMTP password
            if ($envVar === 'SMTP_PASSWORD' && is_string($value)) {
                $decrypted = \Easeo\Cms\Mail\Mailer::decryptSmtpPassword($value);
                if ($decrypted === '') {
                    $warnings[] = 'SMTP_PASSWORD could not be decrypted (legacy crypto-key mismatch); leaving commented in .env';
                    $value      = '<DECRYPT_FAILED>';
                } else {
                    $value = $decrypted;
                }
            }
            $secretsFound[$envVar] = (string) $value;
        }

        // Whatever's left (after dropping secret paths) is the site config
        $remaining = $siteData;
        foreach (array_keys(self::SECRET_PATHS) as $dotKey) {
            $this->dotUnset($remaining, $dotKey);
        }
        // Also drop empty parent arrays we orphaned
        if (isset($remaining['smtp']) && is_array($remaining['smtp']) && empty($remaining['smtp'])) {
            unset($remaining['smtp']);
        }
        $configKeys = array_keys($remaining);

        if (is_file($configPath)) {
            $warnings[] = 'site.config.json already exists — migration would skip writing it (use --force-overwrite to override; not implemented)';
        }
        if (is_file($envPath)) {
            $warnings[] = '.env already exists — new secrets will be appended (not overwritten)';
        }

        return [
            'site_json_exists'        => true,
            'site_config_json_exists' => is_file($configPath),
            'env_exists'              => is_file($envPath),
            'secrets_found'           => $secretsFound,
            'site_config_keys'        => $configKeys,
            'backup_path'             => $backupPath,
            'env_path'                => $envPath,
            'site_config_path'        => $configPath,
            'warnings'                => $warnings,
        ];
    }

    /**
     * Apply the migration. Returns the same shape as plan(), augmented
     * with action results.
     *
     * @return array<string,mixed>
     */
    public function apply(): array
    {
        $plan     = $this->plan();
        $sitePath = $this->dataDir . '/site.json';

        if (!$plan['site_json_exists']) {
            return $plan + ['actions' => []];
        }

        $actions  = [];
        $siteData = json_decode((string) file_get_contents($sitePath), true) ?? [];
        if (!is_array($siteData)) {
            $siteData = [];
        }

        // 1. Backup
        copy($sitePath, $plan['backup_path']);
        $actions[] = "backup created: {$plan['backup_path']}";

        // 2. Write site.config.json (skip if already exists)
        if (!$plan['site_config_json_exists']) {
            $remaining = $siteData;
            foreach (array_keys(self::SECRET_PATHS) as $dotKey) {
                $this->dotUnset($remaining, $dotKey);
            }
            if (isset($remaining['smtp']) && is_array($remaining['smtp']) && empty($remaining['smtp'])) {
                unset($remaining['smtp']);
            }
            $tmp = $plan['site_config_path'] . '.tmp';
            file_put_contents(
                $tmp,
                json_encode($remaining, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            rename($tmp, $plan['site_config_path']);
            $actions[] = "site.config.json written ({$plan['site_config_path']})";
        } else {
            $actions[] = 'site.config.json already exists — skipped';
        }

        // 3. Write/append .env template (commented out for operator review)
        $envBlock  = "\n# === auto-generated from site.json migration (" . date('Y-m-d H:i') . ") ===\n";
        $envBlock .= "# Uncomment + verify before these take effect.\n";
        $envBlock .= "# (SiteConfig falls back to legacy site.json until you do, so behavior is unchanged.)\n";
        foreach ($plan['secrets_found'] as $envVar => $value) {
            $escaped   = '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
            $envBlock .= "# $envVar=$escaped\n";
        }
        $envBlock .= "# === end ===\n";

        file_put_contents($plan['env_path'], $envBlock, FILE_APPEND);
        $actions[] = "env block appended to {$plan['env_path']} (" . count($plan['secrets_found']) . ' secrets)';

        return $plan + ['actions' => $actions];
    }

    private function dotGet(array $data, string $dotKey): mixed
    {
        $cursor = $data;
        foreach (explode('.', $dotKey) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return null;
            }
            $cursor = $cursor[$segment];
        }
        return $cursor;
    }

    private function dotUnset(array &$data, string $dotKey): void
    {
        $segments = explode('.', $dotKey);
        $last     = array_pop($segments);
        $cursor   = &$data;
        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return;
            }
            $cursor = &$cursor[$segment];
        }
        unset($cursor[$last]);
    }
}
