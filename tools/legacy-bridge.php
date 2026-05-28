<?php
/**
 * Legacy-bridge — maps oude `includes/X.php` require-paden naar nieuwe
 * `packages/cms-core/src/legacy/X.php` locatie tijdens Fase A.
 *
 * Geladen via Composer autoload (zie packages/cms-core/composer.json files-array).
 * Wordt verwijderd aan einde Fase B.
 */

if (!defined('EASEO_LEGACY_BRIDGE_LOADED')) {
    define('EASEO_LEGACY_BRIDGE_LOADED', true);

    // Constants die oude code verwacht
    if (!defined('EASEO_ROOT')) {
        // tools/ staat direct in de repo-root — één niveau omhoog = root
        define('EASEO_ROOT', dirname(__DIR__));
    }
    if (!defined('EASEO_APP')) {
        define('EASEO_APP', EASEO_ROOT . '/apps/easeo-website');
    }
    if (!defined('EASEO_DATA')) {
        define('EASEO_DATA', EASEO_APP . '/data');
    }
    if (!defined('EASEO_CORE')) {
        define('EASEO_CORE', EASEO_ROOT . '/packages/cms-core');
    }
    if (!defined('EASEO_TEMPLATES')) {
        define('EASEO_TEMPLATES', EASEO_CORE . '/templates');
    }
    if (!defined('EASEO_LANG')) {
        define('EASEO_LANG', EASEO_CORE . '/lang');
    }
    if (!defined('EASEO_BEHEER')) {
        define('EASEO_BEHEER', EASEO_CORE . '/beheer');
    }
}
