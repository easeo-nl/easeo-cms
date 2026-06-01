<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

use Easeo\Cms\Config\SiteConfig;

/**
 * Discovers and loads modules listed in site.config.json's "modules" array.
 *
 * Resolution: each package name (e.g. "easeo/hello-module") is mapped to a
 * composer.json by trying these paths in order:
 *
 *   1. $appRoot/vendor/{name}/composer.json  (klant-site production)
 *   2. $appRoot/../../packages/{short-name}/composer.json  (monorepo dev,
 *      where easeo/hello-module → packages/hello-module)
 *
 * Each composer.json must declare extra.easeo.module-class (FQN) which
 * implements Easeo\Cms\Module\ModuleInterface. The loader instantiates
 * (no args) and calls register($registry).
 *
 * Fail-soft: a missing/invalid module logs via error_log() and is
 * skipped — does not crash the boot. Use loadOrThrow() in tests for
 * strict semantics.
 */
final class ModuleLoader
{
    private function __construct() {}

    /**
     * Load all modules listed in site.config.json into the registry.
     * Returns the list of successfully-loaded module names.
     *
     * @return list<string>
     */
    public static function loadFromConfig(string $appRoot, Registry $registry): array
    {
        $modules = SiteConfig::get('modules', []);
        if (!is_array($modules)) {
            return [];
        }
        $loaded = [];
        foreach ($modules as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            try {
                self::loadOrThrow($appRoot, $name, $registry);
                $loaded[] = $name;
            } catch (\Throwable $e) {
                error_log("[easeo/cms-core ModuleLoader] failed to load '$name': " . $e->getMessage());
            }
        }
        return $loaded;
    }

    /**
     * Load a single module. Throws on any failure (strict mode for tests).
     */
    public static function loadOrThrow(string $appRoot, string $packageName, Registry $registry): ModuleInterface
    {
        $composerPath = self::resolveComposerPath($appRoot, $packageName);
        if ($composerPath === null) {
            throw new ModuleNotFoundException(
                "Module '$packageName' not found. Tried: $appRoot/vendor/$packageName/composer.json and monorepo path. Run 'composer install'?"
            );
        }

        $manifest = json_decode((string) file_get_contents($composerPath), true);
        if (!is_array($manifest)) {
            throw new InvalidModuleException("composer.json at $composerPath is not valid JSON");
        }

        $class = $manifest['extra']['easeo']['module-class'] ?? null;
        if (!is_string($class) || $class === '') {
            throw new InvalidModuleException(
                "Module '$packageName' composer.json has no 'extra.easeo.module-class' entry"
            );
        }

        if (!class_exists($class)) {
            throw new InvalidModuleException(
                "Module class '$class' for package '$packageName' is not autoloadable. Check composer dump-autoload."
            );
        }

        $instance = new $class();
        if (!$instance instanceof ModuleInterface) {
            throw new InvalidModuleException(
                "Module class '$class' for package '$packageName' does not implement Easeo\\Cms\\Module\\ModuleInterface"
            );
        }

        $instance->register($registry);
        return $instance;
    }

    /**
     * Try to find a composer.json for the given package name in the
     * configured lookup paths.
     */
    private static function resolveComposerPath(string $appRoot, string $packageName): ?string
    {
        $appRoot = rtrim($appRoot, '/');

        // 1. Production path: vendor/{vendor}/{name}/composer.json
        $vendorPath = "$appRoot/vendor/$packageName/composer.json";
        if (is_file($vendorPath)) {
            return $vendorPath;
        }

        // 2. Monorepo-dev path: packages/{short-name}/composer.json
        // For "easeo/hello-module" the short-name is "hello-module".
        $parts = explode('/', $packageName, 2);
        if (count($parts) === 2) {
            $shortName = $parts[1];
            $monorepoPath = dirname($appRoot, 2) . "/packages/$shortName/composer.json";
            if (is_file($monorepoPath)) {
                return $monorepoPath;
            }
        }

        return null;
    }
}
