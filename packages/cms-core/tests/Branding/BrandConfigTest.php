<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Branding;

use Easeo\Cms\Branding\BrandConfig;
use Easeo\Cms\Content\ContentRepository;
use PHPUnit\Framework\TestCase;

final class BrandConfigTest extends TestCase
{
    private static string $tmpDir = '';

    public static function setUpBeforeClass(): void
    {
        // Provide a minimal EASEO_DATA directory with a site.json so
        // ContentRepository::siteValue() does not throw on EASEO_DATA constant.
        self::$tmpDir = sys_get_temp_dir() . '/brandconfig-test-' . uniqid();
        mkdir(self::$tmpDir, 0755, true);
        file_put_contents(self::$tmpDir . '/site.json', json_encode([]));
        if (!defined('EASEO_DATA')) {
            define('EASEO_DATA', self::$tmpDir);
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Flush the JSON cache so subsequent test classes (e.g. ContentRepositoryTest)
        // can re-populate from their own fixture data.
        ContentRepository::resetCache();

        if (self::$tmpDir !== '' && is_dir(self::$tmpDir)) {
            array_map('unlink', glob(self::$tmpDir . '/*') ?: []);
            rmdir(self::$tmpDir);
        }
    }

    public function test_css_properties_returns_root_block_with_custom_props(): void
    {
        $css = BrandConfig::cssProperties();
        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--color-primary:', $css);
        $this->assertStringContainsString('--color-secondary:', $css);
        $this->assertStringContainsString('--color-dark:', $css);
        $this->assertStringContainsString('--font-display:', $css);
        $this->assertStringContainsString('--font-body:', $css);
    }

    public function test_css_properties_uses_default_colors_when_unset(): void
    {
        // Defaults from the source: primary=#2563EB, secondary=#EA580C
        $css = BrandConfig::cssProperties();
        $this->assertStringContainsString('#2563EB', $css);
        $this->assertStringContainsString('#EA580C', $css);
    }

    public function test_google_fonts_url_uses_https_and_display_swap(): void
    {
        $url = BrandConfig::googleFontsUrl();
        $this->assertStringStartsWith('https://fonts.googleapis.com/css2?', $url);
        $this->assertStringContainsString('&display=swap', $url);
    }

    public function test_google_fonts_url_includes_default_fonts(): void
    {
        $url = BrandConfig::googleFontsUrl();
        // Defaults: Outfit (display) + Inter (body)
        $this->assertStringContainsString('Outfit', $url);
        $this->assertStringContainsString('Inter', $url);
    }

    public function test_tailwind_config_includes_var_references(): void
    {
        $config = BrandConfig::tailwindConfig();
        $this->assertStringContainsString('tailwind.config = {', $config);
        $this->assertStringContainsString("'var(--color-primary)'", $config);
        $this->assertStringContainsString("'var(--font-display)'", $config);
        $this->assertStringContainsString("'var(--font-body)'", $config);
    }
}
