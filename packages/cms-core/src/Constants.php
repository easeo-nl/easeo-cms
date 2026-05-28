<?php
declare(strict_types=1);

namespace Easeo\Cms;

/**
 * Bootstrap constants for a klant-site or the easeo-website app.
 *
 * Call once from the entry-file:
 *
 *   \Easeo\Cms\Constants::bootstrap(__DIR__ . '/..');
 *
 * where the argument is the app-root (parent of public/).
 */
final class Constants
{
    public static function bootstrap(string $appRoot): void
    {
        $appRoot = rtrim($appRoot, '/');

        if (!defined('EASEO_APP'))       define('EASEO_APP',       $appRoot);
        if (!defined('EASEO_DATA'))      define('EASEO_DATA',      $appRoot . '/data');

        // CMS-core package root.
        // dirname(__FILE__, 2) → packages/cms-core (this file lives in packages/cms-core/src/Constants.php)
        $core = dirname(__FILE__, 2);
        if (!defined('EASEO_CORE'))      define('EASEO_CORE',      $core);
        if (!defined('EASEO_TEMPLATES')) define('EASEO_TEMPLATES', $core . '/templates');
        if (!defined('EASEO_LANG'))      define('EASEO_LANG',      $core . '/lang');
        if (!defined('EASEO_BEHEER'))    define('EASEO_BEHEER',    $core . '/beheer');

        // Legacy fallback — some tests + integration code reference EASEO_ROOT.
        // It's the parent of apps/ + packages/ — find by walking up from $core.
        if (!defined('EASEO_ROOT'))      define('EASEO_ROOT',      dirname($core, 2));
    }
}
