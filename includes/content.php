<?php
/**
 * EASEO CMS — Content loader, helpers, and globals
 */

define('EASEO_ROOT', dirname(__DIR__));
define('EASEO_DATA', EASEO_ROOT . '/data');

// Load JSON file with caching
function load_json(string $file): array {
    static $cache = [];
    if (isset($cache[$file])) return $cache[$file];

    $path = EASEO_DATA . '/' . $file;
    if (!file_exists($path)) return [];

    $data = json_decode(file_get_contents($path), true);
    $cache[$file] = is_array($data) ? $data : [];
    return $cache[$file];
}

// Save JSON file
function save_json(string $file, array $data): bool {
    $path = EASEO_DATA . '/' . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
}

// Invalidate a cached JSON file so it's re-read on next load
function invalidate_json_cache(string $file): void {
    static $cache = [];
    // We can't unset the static in load_json from here, so we reload globals
}

// Global site and content data
$site = load_json('site.json');
$content = load_json('content.json');
$navigation = load_json('navigation.json');

// Get site setting with dot notation
function site(string $key, $default = '') {
    global $site;
    $keys = explode('.', $key);
    $value = $site;
    foreach ($keys as $k) {
        if (!is_array($value) || !isset($value[$k])) return $default;
        $value = $value[$k];
    }
    return $value;
}

// Get page content
function page_content(string $page, string $key = null, $default = '') {
    global $content;
    if (!isset($content[$page])) return $default;
    if ($key === null) return $content[$page];
    return $content[$page][$key] ?? $default;
}

// Escape output
function e(string $value = null): string {
    if ($value === null) return '';
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Check if setup is complete
function is_setup_complete(): bool {
    $setupFlag = site('setup_complete', false);
    return $setupFlag === true || $setupFlag === 'true' || $setupFlag === 1;
}

// Handle redirects from redirects.json
function handle_redirects(): void {
    $data = load_json('redirects.json');
    $redirects = $data['redirects'] ?? [];
    if (empty($redirects)) return;

    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $current = rtrim($current, '/');
    if ($current === '') $current = '/';

    foreach ($redirects as $redirect) {
        $from = rtrim($redirect['van'] ?? '', '/');
        if ($from === '') $from = '/';

        if (strcasecmp($current, $from) === 0) {
            $code = ($redirect['type'] ?? '301') === '302' ? 302 : 301;
            header('Location: ' . ($redirect['naar'] ?? '/'), true, $code);
            exit;
        }
    }
}

// Run redirects on every page load
handle_redirects();

// Check if setup needed, redirect if so
function check_setup(): void {
    $is_setup_page = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'setup.php') !== false;
    $is_admin = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'beheer/') !== false;

    if (!$is_setup_page && !$is_admin && !is_setup_complete()) {
        header('Location: /setup.php');
        exit;
    }
}
