<?php
/**
 * Development router for php -S
 * Simulates .htaccess rewrite rules for local testing.
 * Usage: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Block .json, .log, .md files (before static file serving)
if (preg_match('/\.(json|log|md)$/', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

// Block direct access to includes/ directory
if (preg_match('#^/includes/#', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

// Serve static files directly
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // Let PHP serve the file
}

// Route mapping (simulates .htaccess)
$routes = [
    '#^/sitemap\.xml$#' => '/sitemap.php',
    '#^/feed/?$#' => '/feed.php',
    '#^/blog/?$#' => '/blog.php',
    '#^/blog/categorie/([^/]+)/?$#' => '/blog.php?categorie=$1',
    '#^/blog/pagina/([0-9]+)/?$#' => '/blog.php?pagina=$1',
    '#^/blog/([^/]+)/?$#' => '/blog-post.php?slug=$1',
    '#^/privacyverklaring/?$#' => '/privacyverklaring.php',
    '#^/voorwaarden/?$#' => '/voorwaarden.php',
    '#^/cookiebeleid/?$#' => '/cookiebeleid.php',
    '#^/contact/?$#' => '/contact.php',
];

foreach ($routes as $pattern => $target) {
    if (preg_match($pattern, $uri, $matches)) {
        $resolved = preg_replace($pattern, $target, $uri);
        $parts = explode('?', $resolved, 2);
        if (isset($parts[1])) {
            parse_str($parts[1], $params);
            $_GET = array_merge($_GET, $params);
        }
        require __DIR__ . $parts[0];
        return true;
    }
}

// Beheer (admin UI): boot App first so modules are registered, then hand off.
if (preg_match('#^/beheer(/.*)?$#', $uri)) {
    require __DIR__ . '/beheer-entry.php';
    return true;
}

// Homepage
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// Module routes: try to match against registry BEFORE falling back to the pagina-router.
// Boot App here (or reuse the already-booted instance) to consult the RouteTable.
// This must happen after core routes (above) so core routes always win.
if (!class_exists(\Easeo\Cms\App::class, false)) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
$_devRouterApp = \Easeo\Cms\App::current();
if ($_devRouterApp === null) {
    try {
        $_devRouterApp = \Easeo\Cms\App::boot(__DIR__ . '/..');
    } catch (\Throwable $e) {
        // Boot failure → skip module routing, fall through to pagina-router / 404.
        error_log('[easeo router.php] App::boot failed: ' . $e->getMessage());
        $_devRouterApp = null;
    }
}
if ($_devRouterApp !== null) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_devRoute = $_devRouterApp->registry()->routes()->match($method, $uri);
    if ($_devRoute !== null) {
        // Module route matched — hand off to module-router.php to dispatch it.
        require __DIR__ . '/module-router.php';
        return true;
    }
}

// Dynamic pages: parent/child slugs
if (preg_match('#^/([a-z0-9-]+)/([a-z0-9-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1] . '/' . $matches[2];
    require __DIR__ . '/pagina-router.php';
    return true;
}

// Dynamic pages: single slug (catch-all)
if (preg_match('#^/([a-z0-9-]+)/?$#', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/pagina-router.php';
    return true;
}

// 404
http_response_code(404);
require __DIR__ . '/404.php';
return true;
