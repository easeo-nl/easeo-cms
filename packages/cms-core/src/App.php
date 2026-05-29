<?php
declare(strict_types=1);

namespace Easeo\Cms;

use Easeo\Cms\Content\ContentRepository;

/**
 * App entry-point for klant-sites built on easeo/cms-core.
 *
 * Typical usage (e.g. apps/_skeleton/public/index.php):
 *
 *   require __DIR__ . '/../vendor/autoload.php';
 *   \Easeo\Cms\App::boot(__DIR__ . '/..')->run();
 *
 * boot() initialises constants, session, and locale.
 * run() is intentionally a no-op placeholder — site-apps with multiple
 * routes typically dispatch from their own entry-file (Apache .htaccess
 * rewrites URLs to index.php / blog.php / contact.php etc. on Hostinger
 * shared). A site-app that wants a single-file front-controller can
 * extend or replace run() in its own entry.
 */
final class App
{
    private string $appRoot;

    private function __construct(string $appRoot)
    {
        $this->appRoot = $appRoot;
    }

    public static function boot(string $appRoot): self
    {
        $appRoot = rtrim($appRoot, '/');

        Constants::bootstrap($appRoot);
        self::secureSession();

        return new self($appRoot);
    }

    /**
     * Default no-op. Override or replace in site-app entry-files for
     * front-controller-style routing. The skeleton's index.php calls
     * this so the wire is in place when run() gains behaviour.
     */
    public function run(): void
    {
        // intentional no-op
    }

    public function appRoot(): string
    {
        return $this->appRoot;
    }

    private static function secureSession(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        // Match the legacy beheer/inc/auth.php session-hardening defaults.
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');

        // cookie_secure is only meaningful when the request is HTTPS; turning
        // it on for plain HTTP (e.g. PHP built-in server in tests) silently
        // breaks session cookies. Detect and adapt.
        $https = ($_SERVER['HTTPS'] ?? 'off') !== 'off'
              || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        if ($https) {
            ini_set('session.cookie_secure', '1');
        }

        // CLI / phpunit runs don't have a request scope; trying to start a
        // session there spams "headers already sent" warnings. Skip.
        if (PHP_SAPI === 'cli') {
            return;
        }

        @session_start();
    }
}
