<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Config;

use Easeo\Cms\Config\SiteConfig;
use PHPUnit\Framework\TestCase;

class SiteConfigTest extends TestCase
{
    private string $tmpData = '';
    private string $tmpApp  = '';
    private string $originalEaseoData = '';
    private string $originalEaseoApp  = '';

    protected function setUp(): void
    {
        $this->tmpData = sys_get_temp_dir() . '/siteconfig-data-' . uniqid('', true);
        $this->tmpApp  = sys_get_temp_dir() . '/siteconfig-app-'  . uniqid('', true);
        mkdir($this->tmpData, 0755, true);
        mkdir($this->tmpApp,  0755, true);

        $this->originalEaseoData = (string)(getenv('EASEO_DATA') ?: '');
        $this->originalEaseoApp  = (string)(getenv('EASEO_APP')  ?: '');

        // Point both env vars to empty tmp dirs so no real config/template leaks in.
        putenv('EASEO_DATA=' . $this->tmpData);
        putenv('EASEO_APP='  . $this->tmpApp);
        SiteConfig::resetCache();
    }

    protected function tearDown(): void
    {
        SiteConfig::resetCache();

        if ($this->originalEaseoData !== '') {
            putenv('EASEO_DATA=' . $this->originalEaseoData);
        } else {
            putenv('EASEO_DATA');
        }
        if ($this->originalEaseoApp !== '') {
            putenv('EASEO_APP=' . $this->originalEaseoApp);
        } else {
            putenv('EASEO_APP');
        }

        if ($this->tmpData !== '' && is_dir($this->tmpData)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmpData));
        }
        if ($this->tmpApp !== '' && is_dir($this->tmpApp)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmpApp));
        }
    }

    public function testGetReturnsDefaultWhenNoConfigExists(): void
    {
        $result = SiteConfig::get('brand.color_primary', '#333333');
        $this->assertSame('#333333', $result);
    }

    public function testGetReturnsNullDefaultByDefault(): void
    {
        $result = SiteConfig::get('nonexistent.key');
        $this->assertNull($result);
    }

    public function testGetReadsNestedValue(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['brand' => ['color_primary' => '#FF0000']])
        );
        SiteConfig::resetCache();

        $result = SiteConfig::get('brand.color_primary');
        $this->assertSame('#FF0000', $result);
    }

    public function testSetThenGetRoundTrips(): void
    {
        SiteConfig::set('brand.color_primary', '#0000FF');
        $result = SiteConfig::get('brand.color_primary');
        $this->assertSame('#0000FF', $result);
    }

    public function testSetThenSavePersistsToDisk(): void
    {
        SiteConfig::set('company.name', 'EASEO BV');
        SiteConfig::save();

        $path = $this->tmpData . '/site.config.json';
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertSame('EASEO BV', $data['company']['name'] ?? null);
    }

    public function testAllReturnsFullArray(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['key1' => 'val1', 'key2' => 'val2'])
        );
        SiteConfig::resetCache();

        $all = SiteConfig::all();
        $this->assertIsArray($all);
        $this->assertSame('val1', $all['key1'] ?? null);
        $this->assertSame('val2', $all['key2'] ?? null);
    }

    public function testFallbackToSiteTemplateJson(): void
    {
        // No site.config.json, but a site.template.json via EASEO_APP (already set in setUp)
        file_put_contents($this->tmpApp . '/site.template.json', json_encode([
            'brand' => ['color_primary' => '#AABBCC'],
        ]));
        SiteConfig::resetCache();

        $result = SiteConfig::get('brand.color_primary', 'default');
        $this->assertSame('#AABBCC', $result);
    }

    public function testFallbackToLegacySiteJson(): void
    {
        // No site.config.json, no site.template.json, but legacy site.json
        file_put_contents(
            $this->tmpData . '/site.json',
            json_encode(['brand' => ['color_primary' => '#LEGACY']])
        );
        SiteConfig::resetCache();

        $result = SiteConfig::get('brand.color_primary', 'default');
        $this->assertSame('#LEGACY', $result);
    }

    public function testResetCacheClearsInMemoryState(): void
    {
        SiteConfig::set('foo', 'bar');
        $this->assertSame('bar', SiteConfig::get('foo'));

        SiteConfig::resetCache();

        // After reset, the in-memory value is gone (no config file exists)
        $this->assertNull(SiteConfig::get('foo'));
    }

    public function testSaveWritesValidJson(): void
    {
        SiteConfig::set('foo', 'bar');
        SiteConfig::set('nested.key', 'value');
        SiteConfig::save();

        $path = $this->tmpData . '/site.config.json';
        $this->assertFileExists($path);

        $data = json_decode(file_get_contents($path), true);
        $this->assertSame('bar', $data['foo'] ?? null);
        $this->assertSame('value', $data['nested']['key'] ?? null);
    }

    public function testSiteConfigJsonTakesPriorityOverLegacy(): void
    {
        // site.config.json has newer value, site.json has old value
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['brand' => ['color_primary' => '#NEW']])
        );
        file_put_contents(
            $this->tmpData . '/site.json',
            json_encode(['brand' => ['color_primary' => '#OLD']])
        );
        SiteConfig::resetCache();

        $result = SiteConfig::get('brand.color_primary');
        $this->assertSame('#NEW', $result);
    }
}
