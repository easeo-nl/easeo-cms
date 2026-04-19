<?php
/**
 * EASEO CMS — Internationalization (i18n)
 *
 * Usage: t('key') returns the translated string for the current locale.
 * Language files are stored in lang/{locale}.json.
 * The locale is set in data/site.json → "language" (default: "en").
 */

function t(string $key, array $params = []): string {
    static $strings = null;
    static $fallback = null;

    if ($strings === null) {
        $locale = function_exists('site') ? site('language', 'en') : 'en';
        $locale = preg_replace('/[^a-z]/', '', strtolower($locale));

        $lang_dir = dirname(__DIR__) . '/lang';
        $file = $lang_dir . '/' . $locale . '.json';

        if (file_exists($file)) {
            $strings = json_decode(file_get_contents($file), true) ?? [];
        } else {
            $strings = [];
        }

        // Always load English as fallback
        if ($locale !== 'en') {
            $en_file = $lang_dir . '/en.json';
            $fallback = file_exists($en_file) ? (json_decode(file_get_contents($en_file), true) ?? []) : [];
        } else {
            $fallback = [];
        }
    }

    $text = $strings[$key] ?? $fallback[$key] ?? $key;

    // Replace {param} placeholders
    foreach ($params as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }

    return $text;
}

/**
 * Get available locales by scanning lang/ directory.
 */
function available_locales(): array {
    $dir = dirname(__DIR__) . '/lang';
    $locales = [];
    foreach (glob($dir . '/*.json') as $file) {
        $locales[] = basename($file, '.json');
    }
    return $locales;
}

/**
 * Get locale display name.
 */
function locale_name(string $code): string {
    $names = [
        'en' => 'English',
        'nl' => 'Nederlands',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'pt' => 'Português',
        'pl' => 'Polski',
    ];
    return $names[$code] ?? strtoupper($code);
}
