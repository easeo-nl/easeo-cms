<?php
/**
 * Development router for php -S
 * Simulates .htaccess rewrite rules for local testing.
 * Usage: php -S localhost:8000 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Serve static files directly
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // Let PHP serve the file
}

// Block .json, .log, .md files
if (preg_match('/\.(json|log|md)$/', $uri)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
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
    '#^/beheer/?(.*)$#' => null, // Pass through
];

foreach ($routes as $pattern => $target) {
    if (preg_match($pattern, $uri, $matches)) {
        if ($target === null) return false;
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

// Homepage
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// 404
http_response_code(404);
require __DIR__ . '/404.php';
return true;
