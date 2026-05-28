<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Lang;

use Easeo\Cms\Lang\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private static string $tmpDir = '';

    public static function setUpBeforeClass(): void
    {
        // Provide a minimal EASEO_DATA directory with a site.json so
        // ContentRepository::siteValue() does not throw on EASEO_DATA constant.
        self::$tmpDir = sys_get_temp_dir() . '/translator-test-' . uniqid();
        mkdir(self::$tmpDir, 0755, true);
        file_put_contents(self::$tmpDir . '/site.json', json_encode(['language' => 'en']));
        if (!defined('EASEO_DATA')) {
            define('EASEO_DATA', self::$tmpDir);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$tmpDir !== '' && is_dir(self::$tmpDir)) {
            array_map('unlink', glob(self::$tmpDir . '/*') ?: []);
            rmdir(self::$tmpDir);
        }
    }

    protected function setUp(): void
    {
        Translator::reset();
    }

    public function test_translate_returns_string_for_unknown_key(): void
    {
        // Unknown keys fall back to the key itself
        $result = Translator::translate('this-key-does-not-exist');
        $this->assertSame('this-key-does-not-exist', $result);
    }

    public function test_translate_substitutes_params(): void
    {
        // The function signature accepts $params and replaces {name} placeholders.
        // We can't assert a translated string (it depends on lang/ files), but we can
        // verify the param substitution behavior on the fallback path:
        $result = Translator::translate('hello-{name}', ['name' => 'world']);
        $this->assertStringContainsString('world', $result);
    }

    public function test_available_locales_returns_array(): void
    {
        $locales = Translator::availableLocales();
        $this->assertIsArray($locales);
    }

    public function test_locale_name_returns_dutch_for_nl(): void
    {
        $this->assertSame('Nederlands', Translator::localeName('nl'));
    }

    public function test_locale_name_returns_english_for_en(): void
    {
        $this->assertSame('English', Translator::localeName('en'));
    }

    public function test_locale_name_uppercases_unknown_codes(): void
    {
        $this->assertSame('XX', Translator::localeName('xx'));
    }
}
