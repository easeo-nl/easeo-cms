<?php
namespace Easeo\Cms\Tests\Content;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Config\SiteConfig;
use PHPUnit\Framework\TestCase;

class ContentRepositoryTest extends TestCase {
    private string $tmp    = '';
    private string $tmpApp = '';

    protected function setUp(): void
    {
        $this->tmp    = sys_get_temp_dir() . '/content-data-' . uniqid('', true);
        $this->tmpApp = sys_get_temp_dir() . '/content-app-'  . uniqid('', true);
        mkdir($this->tmp,    0755, true);
        mkdir($this->tmpApp, 0755, true);
        SiteConfig::resetCache();
    }

    protected function tearDown(): void
    {
        SiteConfig::resetCache();
        ContentRepository::resetCache();
        putenv('EASEO_APP');
        if ($this->tmp !== '' && is_dir($this->tmp)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmp));
        }
        if ($this->tmpApp !== '' && is_dir($this->tmpApp)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmpApp));
        }
    }

    public function testSiteValueLeestUitSiteJson(): void {
        file_put_contents($this->tmp . '/site.json', json_encode([
            'brand' => ['color_primary' => '#FF0000'],
        ]));

        putenv('EASEO_DATA=' . $this->tmp);
        putenv('EASEO_APP='  . $this->tmpApp); // empty dir — no template fallback
        SiteConfig::resetCache(); // flush any cache loaded before putenv was set

        $value = ContentRepository::siteValue('brand.color_primary', '#000');
        $this->assertSame('#FF0000', $value);

        $fallback = ContentRepository::siteValue('nonexistent.key', 'fallback');
        $this->assertSame('fallback', $fallback);
    }
}
