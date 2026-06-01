<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};

final class CmsCoreVersionCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return 'cms-core version';
    }

    public function run(): CheckResult
    {
        $version = $this->detectVersion();
        return new CheckResult(
            CheckStatus::Ok,
            $this->label(),
            $version,
        );
    }

    private function detectVersion(): string
    {
        // Check data/.cms-core-version (written by VersionTracker on boot)
        $trackerFile = $this->appRoot . '/data/.cms-core-version';
        if (is_file($trackerFile)) {
            $v = trim((string) file_get_contents($trackerFile));
            if ($v !== '') {
                return $v;
            }
        }

        // Fall back to installed.json
        $installed = $this->appRoot . '/vendor/composer/installed.json';
        if (is_file($installed)) {
            $data = json_decode((string) file_get_contents($installed), true);
            $packages = $data['packages'] ?? $data ?? [];
            if (is_array($packages)) {
                foreach ($packages as $p) {
                    if (($p['name'] ?? null) === 'easeo/cms-core') {
                        return (string) ($p['version'] ?? 'unknown');
                    }
                }
            }
        }

        return 'dev-monorepo';
    }
}
