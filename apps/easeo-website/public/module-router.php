<?php
/**
 * EASEO CMS — Module-route dispatcher
 *
 * Called as a fallback entry-point when no core route matched the request URI.
 * Boots App (if not already booted), consults the module Registry's RouteTable,
 * and dispatches the first matching handler.
 *
 * Used by:
 *   - router.php (php -S dev server) as a fallback include
 *   - .htaccess can route unmatched URIs here as a fallback (optional)
 *
 * If no module route matches, returns a clean 404.
 */
declare(strict_types=1);

// Ensure vendor autoload is available whether we were included from router.php
// (which already did require_once) or hit directly.
if (!function_exists('class_exists') || !class_exists(\Easeo\Cms\App::class, false)) {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        http_response_code(503);
        echo 'vendor/autoload.php not found — run composer install in apps/easeo-website/';
        exit;
    }
    require_once $autoload;
}

// Boot App if not already booted (idempotent: App::boot stores the instance in App::current())
$_moduleRouterApp = \Easeo\Cms\App::current();
if ($_moduleRouterApp === null) {
    try {
        $_moduleRouterApp = \Easeo\Cms\App::boot(__DIR__ . '/..');
    } catch (\Throwable $e) {
        http_response_code(503);
        echo 'CMS boot failure: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit;
    }
}

// Determine the incoming method + path
$_mrMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_mrPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$_mrPath   = '/' . ltrim((string) $_mrPath, '/');
// Normalise trailing slash (strip, except root)
if ($_mrPath !== '/' && str_ends_with($_mrPath, '/')) {
    $_mrPath = rtrim($_mrPath, '/');
}

// Match against module-registered routes
$_mrRoute = $_moduleRouterApp->registry()->routes()->match($_mrMethod, $_mrPath);

if ($_mrRoute === null) {
    // No module route matched — 404
    http_response_code(404);
    $file404 = __DIR__ . '/404.php';
    if (is_file($file404)) {
        require $file404;
    } else {
        echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>404</title></head>'
           . '<body><h1>404 — Pagina niet gevonden</h1></body></html>';
    }
    return true;
}

// Invoke the handler and emit its return value (if any)
try {
    ob_start();
    $result = ($_mrRoute['handler'])();
    $buffered = ob_get_clean();
    echo $buffered;
    if ($result !== null) {
        echo is_string($result) ? $result : (string) $result;
    }
} catch (\Throwable $e) {
    ob_end_clean();
    error_log('[easeo/cms-core module-router] handler for "' . $_mrPath . '" threw: ' . $e->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="nl"><head><meta charset="UTF-8"><title>500</title></head>'
       . '<body><h1>500 — Serverfout</h1></body></html>';
}
return true;
