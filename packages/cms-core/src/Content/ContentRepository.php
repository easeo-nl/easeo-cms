<?php
namespace Easeo\Cms\Content;

class ContentRepository
{
    public static function loadJson(string $file) : array
    {
        static $cache = [];
        if (isset($cache[$file])) {
            return $cache[$file];
        }
        $path = EASEO_DATA . '/' . $file;
        if (!file_exists($path)) {
            return [];
        }
        $data = json_decode(file_get_contents($path), true);
        $cache[$file] = is_array($data) ? $data : [];
        return $cache[$file];
    }
    public static function saveJson(string $file, array $data) : bool
    {
        $path = EASEO_DATA . '/' . $file;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
    }
    public static function invalidateJsonCache(string $file) : void
    {
        static $cache = [];
        // We can't unset the static in load_json from here, so we reload globals
    }
    public static function siteValue(string $key, $default = '')
    {
        $site = self::loadJson('site.json');
        $keys = explode('.', $key);
        $value = $site;
        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }
    public static function pageContent(string $page, string $key = null, $default = '')
    {
        $content = self::loadJson('content.json');
        if (!isset($content[$page])) {
            return $default;
        }
        if ($key === null) {
            return $content[$page];
        }
        return $content[$page][$key] ?? $default;
    }
    public static function escape(string $value = null) : string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    public static function isSetupComplete() : bool
    {
        $setupFlag = self::siteValue('setup_complete', false);
        return $setupFlag === true || $setupFlag === 'true' || $setupFlag === 1;
    }
    public static function handleRedirects() : void
    {
        $data = self::loadJson('redirects.json');
        $redirects = $data['redirects'] ?? [];
        if (empty($redirects)) {
            return;
        }
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $current = rtrim($current, '/');
        if ($current === '') {
            $current = '/';
        }
        foreach ($redirects as $redirect) {
            $from = rtrim($redirect['van'] ?? '', '/');
            if ($from === '') {
                $from = '/';
            }
            if (strcasecmp($current, $from) === 0) {
                $code = ($redirect['type'] ?? '301') === '302' ? 302 : 301;
                header('Location: ' . ($redirect['naar'] ?? '/'), true, $code);
                exit;
            }
        }
    }
    public static function checkSetup() : void
    {
        $is_setup_page = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'setup.php') !== false;
        $is_admin = strpos($_SERVER['SCRIPT_NAME'] ?? '', 'beheer/') !== false;
        if (!$is_setup_page && !$is_admin && !self::isSetupComplete()) {
            header('Location: /setup.php');
            exit;
        }
    }
}
