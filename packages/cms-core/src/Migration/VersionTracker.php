<?php
declare(strict_types=1);

namespace Easeo\Cms\Migration;

/**
 * Tracks the cms-core version last seen by App::boot() — used to
 * decide whether OPcache should be reset (which happens once per
 * symlink-deploy when a new vendor/ tree slides in).
 *
 * Storage: $dataDir/.cms-core-version (atomic tmp+rename).
 */
final class VersionTracker
{
    public function __construct(private readonly string $dataDir) {}

    public function lastSeen(): ?string
    {
        $file = $this->path();
        if (!is_file($file)) {
            return null;
        }
        $raw = trim((string) file_get_contents($file));
        return $raw === '' ? null : $raw;
    }

    public function record(string $version): void
    {
        $file = $this->path();
        $tmp = $file . '.tmp';
        file_put_contents($tmp, $version, LOCK_EX);
        rename($tmp, $file);
    }

    public function hasChanged(string $current): bool
    {
        $last = $this->lastSeen();
        return $last === null ? false : ($last !== $current);
    }

    private function path(): string
    {
        return $this->dataDir . '/.cms-core-version';
    }

    /**
     * Read the currently-installed cms-core version from composer's
     * installed.json (production), or fall back to 'dev-monorepo' (we
     * are likely inside the monorepo with the package as path-repo).
     */
    public static function detectCurrentVersion(string $appRoot): string
    {
        $installedJson = $appRoot . '/vendor/composer/installed.json';
        if (is_file($installedJson)) {
            $data = json_decode((string) file_get_contents($installedJson), true);
            $packages = $data['packages'] ?? $data ?? [];
            if (is_array($packages)) {
                foreach ($packages as $pkg) {
                    if (($pkg['name'] ?? null) === 'easeo/cms-core') {
                        return (string) ($pkg['version'] ?? 'dev-unknown');
                    }
                }
            }
        }
        return 'dev-monorepo';
    }
}
