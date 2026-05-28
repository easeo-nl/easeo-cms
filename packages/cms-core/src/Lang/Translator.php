<?php
declare(strict_types=1);

namespace Easeo\Cms\Lang;

use Easeo\Cms\Content\ContentRepository;

final class Translator
{
    /** @var array<string,string>|null */
    private static ?array $strings = null;

    /** @var array<string,string>|null */
    private static ?array $fallback = null;

    /**
     * Translate a key for the current locale. Falls back to English, then to the key itself.
     *
     * @param array<string,string> $params Placeholders like {name} are substituted.
     */
    public static function translate(string $key, array $params = []): string
    {
        if (self::$strings === null) {
            self::loadLanguageFiles();
        }
        $text = self::$strings[$key] ?? self::$fallback[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace('{' . $k . '}', $v, $text);
        }
        return $text;
    }

    /**
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        $dir = self::langDir();
        $locales = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $locales[] = basename($file, '.json');
        }
        return $locales;
    }

    public static function localeName(string $code): string
    {
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

    /**
     * Reset the in-memory cache. Useful for tests and after switching locales.
     */
    public static function reset(): void
    {
        self::$strings = null;
        self::$fallback = null;
    }

    private static function loadLanguageFiles(): void
    {
        $locale = ContentRepository::siteValue('language', 'en');
        $locale = preg_replace('/[^a-z]/', '', strtolower((string) $locale));

        $dir = self::langDir();
        $file = "$dir/$locale.json";
        self::$strings = file_exists($file)
            ? (json_decode((string) file_get_contents($file), true) ?? [])
            : [];

        if ($locale !== 'en') {
            $en = "$dir/en.json";
            self::$fallback = file_exists($en)
                ? (json_decode((string) file_get_contents($en), true) ?? [])
                : [];
        } else {
            self::$fallback = [];
        }
    }

    private static function langDir(): string
    {
        // __DIR__ = packages/cms-core/src/Lang
        // dirname(__DIR__, 2) = packages/cms-core
        // Resolves to packages/cms-core/lang regardless of caller location.
        return dirname(__DIR__, 2) . '/lang';
    }
}
