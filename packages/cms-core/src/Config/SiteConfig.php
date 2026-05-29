<?php
declare(strict_types=1);

namespace Easeo\Cms\Config;

/**
 * Reads (and writes) site.config.json — the publicly-editable
 * branding/content config. Used by the beheer-UI for editable values like
 * GTM ID, company name, brand colors.
 *
 * Secrets do NOT live here — see Environment + .env.
 *
 * Storage: $appRoot/data/site.config.json (gitignored). Falls back to
 * $appRoot/site.template.json when site.config.json doesn't exist yet
 * (first request after deploy, before Bootstrapper has run).
 * As a final fallback, reads legacy data/site.json during the transition
 * period before the migration script splits it.
 */
final class SiteConfig
{
    private static ?array $cache = null;
    private static string $cacheSource = '';

    public static function get(string $dotKey, mixed $default = null): mixed
    {
        $data = self::load();
        $segments = explode('.', $dotKey);
        $cursor = $data;
        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }
        return $cursor;
    }

    public static function set(string $dotKey, mixed $value): void
    {
        $data = self::load();
        $segments = explode('.', $dotKey);
        $cursor = &$data;
        foreach ($segments as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
        $cursor = $value;
        self::$cache = $data;
    }

    public static function save(): void
    {
        if (self::$cache === null) {
            return;
        }
        $path = self::filePath();
        if ($path === null) {
            throw new \RuntimeException('Cannot save SiteConfig: EASEO_DATA not defined');
        }
        $tmp = $path . '.tmp';
        file_put_contents(
            $tmp,
            json_encode(self::$cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        rename($tmp, $path);
    }

    public static function all(): array
    {
        return self::load();
    }

    public static function resetCache(): void
    {
        self::$cache = null;
        self::$cacheSource = '';
    }

    private static function load(): array
    {
        $path = self::filePath();
        $source = $path ?? '';
        if (self::$cache !== null && self::$cacheSource === $source) {
            return self::$cache;
        }

        // 1. Primary: site.config.json
        if ($path !== null && is_file($path)) {
            $raw = file_get_contents($path);
            $data = json_decode((string) $raw, true);
            if (is_array($data)) {
                self::$cache = $data;
                self::$cacheSource = $source;
                return $data;
            }
        }

        // 2. Fall back to site.template.json (first deploy, before Bootstrapper ran)
        $template = self::templatePath();
        if ($template !== null && is_file($template)) {
            $raw = file_get_contents($template);
            $data = json_decode((string) $raw, true);
            if (is_array($data)) {
                self::$cache = $data;
                self::$cacheSource = $source;
                return $data;
            }
        }

        // 3. Legacy fallback: data/site.json (before migration script has split it)
        $legacy = self::legacyPath();
        if ($legacy !== null && is_file($legacy)) {
            $raw = file_get_contents($legacy);
            $data = json_decode((string) $raw, true);
            if (is_array($data)) {
                self::$cache = $data;
                self::$cacheSource = $source;
                return $data;
            }
        }

        self::$cache = [];
        self::$cacheSource = $source;
        return [];
    }

    private static function filePath(): ?string
    {
        $env = getenv('EASEO_DATA');
        $dataDir = ($env !== false && $env !== '') ? $env : (defined('EASEO_DATA') ? constant('EASEO_DATA') : null);
        return $dataDir === null ? null : $dataDir . '/site.config.json';
    }

    private static function templatePath(): ?string
    {
        $env = getenv('EASEO_APP');
        $appRoot = ($env !== false && $env !== '') ? $env : (defined('EASEO_APP') ? constant('EASEO_APP') : null);
        return $appRoot === null ? null : $appRoot . '/site.template.json';
    }

    private static function legacyPath(): ?string
    {
        $env = getenv('EASEO_DATA');
        $dataDir = ($env !== false && $env !== '') ? $env : (defined('EASEO_DATA') ? constant('EASEO_DATA') : null);
        return $dataDir === null ? null : $dataDir . '/site.json';
    }
}
