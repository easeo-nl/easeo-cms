<?php
declare(strict_types=1);

namespace Easeo\Cms\Config;

use Dotenv\Dotenv;

/**
 * Thin wrapper around vlucas/phpdotenv. Loads .env (and .env.local override)
 * from the app-root and exposes typed read methods.
 *
 * The loaded values land in $_ENV / $_SERVER / getenv() — callers can use
 * Environment::get() for a consistent type-cast accessor or fall back to
 * the standard PHP helpers.
 */
final class Environment
{
    private static bool $loaded = false;
    private static string $sourceRoot = '';

    public static function load(string $appRoot): void
    {
        $appRoot = rtrim($appRoot, '/');
        if (self::$loaded && self::$sourceRoot === $appRoot) {
            return; // already loaded for this app-root
        }
        if (!is_dir($appRoot)) {
            return;
        }

        // .env may not exist (e.g. fresh skeleton before install). That's OK.
        $dotenv = Dotenv::createMutable($appRoot, ['.env.local', '.env']);
        $dotenv->safeLoad();

        self::$loaded = true;
        self::$sourceRoot = $appRoot;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return (string) $value;
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $raw = self::get($key);
        if ($raw === null) {
            return $default;
        }
        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $raw = self::get($key);
        return $raw === null ? $default : (int) $raw;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null) {
            throw new \RuntimeException("Required environment variable '$key' is not set");
        }
        return $value;
    }

    /**
     * Test helper — reset internal state so a second load() with a different
     * app-root actually reloads. Not for production use.
     */
    public static function reset(): void
    {
        self::$loaded = false;
        self::$sourceRoot = '';
    }
}
