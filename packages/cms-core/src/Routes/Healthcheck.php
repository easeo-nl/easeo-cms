<?php
declare(strict_types=1);

namespace Easeo\Cms\Routes;

use Easeo\Cms\Config\Environment;
use Easeo\Cms\Migration\SchemaVersion;

final class Healthcheck
{
    /**
     * Handle a healthcheck request.
     *
     * Without a matching secret: returns only {"status":"ok"}.
     * With a matching secret: returns extended payload with version, schema, writable checks.
     *
     * Never throws — always returns a JSON-serialisable array.
     *
     * @return array<string,mixed>
     */
    public static function handle(string $appRoot, ?string $providedSecret = null): array
    {
        try {
            $expectedSecret = Environment::get('HEALTHCHECK_SECRET', '');
            $basicOnly      = true;

            if (
                $expectedSecret !== null
                && $expectedSecret !== ''
                && $providedSecret !== null
                && hash_equals($expectedSecret, $providedSecret)
            ) {
                $basicOnly = false;
            }

            if ($basicOnly) {
                return ['status' => 'ok'];
            }

            $dataDir = $appRoot . '/data';
            return [
                'status'           => 'ok',
                'cms_core_version' => self::detectCmsCoreVersion($appRoot),
                'schema_version'   => is_dir($dataDir) ? (new SchemaVersion($dataDir))->current() : null,
                'data_writable'    => is_dir($dataDir) && is_writable($dataDir),
                'php_version'      => PHP_VERSION,
                'timestamp'        => date('c'),
            ];
        } catch (\Throwable $e) {
            // Never throw — always return JSON
            return ['status' => 'ok'];
        }
    }

    private static function detectCmsCoreVersion(string $appRoot): string
    {
        // Check data/.cms-core-version first (written by VersionTracker on boot)
        $trackerFile = $appRoot . '/data/.cms-core-version';
        if (is_file($trackerFile)) {
            $v = trim((string) file_get_contents($trackerFile));
            if ($v !== '') {
                return $v;
            }
        }

        // Fall back to composer installed.json
        $installed = $appRoot . '/vendor/composer/installed.json';
        if (!is_file($installed)) {
            return 'dev-monorepo';
        }
        $data     = json_decode((string) file_get_contents($installed), true);
        $packages = $data['packages'] ?? $data ?? [];
        if (is_array($packages)) {
            foreach ($packages as $p) {
                if (($p['name'] ?? null) === 'easeo/cms-core') {
                    return (string) ($p['version'] ?? 'unknown');
                }
            }
        }
        return 'unknown';
    }
}
