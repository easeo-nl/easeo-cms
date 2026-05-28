<?php
namespace Easeo\Cms\Tests\Content;

use Easeo\Cms\Content\ContentRepository;
use PHPUnit\Framework\TestCase;

class ContentRepositoryTest extends TestCase {
    public function testSiteValueLeestUitSiteJson(): void {
        $tmp = sys_get_temp_dir() . '/content-test-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/site.json', json_encode([
            'brand' => ['color_primary' => '#FF0000'],
        ]));

        putenv('EASEO_DATA=' . $tmp);
        if (!defined('EASEO_DATA')) define('EASEO_DATA', $tmp);

        $value = ContentRepository::siteValue('brand.color_primary', '#000');
        $this->assertSame('#FF0000', $value);

        $fallback = ContentRepository::siteValue('nonexistent.key', 'fallback');
        $this->assertSame('fallback', $fallback);

        shell_exec('rm -rf ' . escapeshellarg($tmp));
    }
}
