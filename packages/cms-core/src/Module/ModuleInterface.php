<?php
declare(strict_types=1);

namespace Easeo\Cms\Module;

/**
 * Contract that every cms-core module package implements.
 *
 * The module's composer.json declares the implementing class via
 * `extra.easeo.module-class` (e.g. `"Easeo\\Hello\\Module"`), and the
 * ModuleLoader (batch 2) instantiates it and calls `register()` exactly
 * once during App::boot.
 *
 * Modules register their extension points (routes, hooks, beheer-pages,
 * config-schemas) on the passed Registry. They do NOT keep a reference
 * to the registry — anything they need at runtime must be wired through
 * the closures they register.
 */
interface ModuleInterface
{
    /**
     * Machine-readable identifier. Convention: matches the composer
     * package name (e.g. "easeo/hello-module").
     */
    public function name(): string;

    /**
     * Semver-like version string. Used for diagnostic display only.
     */
    public function version(): string;

    /**
     * Register extension points on the registry. Called once at boot.
     */
    public function register(Registry $registry): void;
}
