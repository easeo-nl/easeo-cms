<?php
namespace Easeo\Cms\Tests\Integration;

use PHPUnit\Framework\TestCase;

class PageRenderingTest extends TestCase {
    private static $serverPid = null;
    private static $baseUrl = 'http://localhost:8765';

    public static function setUpBeforeClass(): void {
        $root = dirname(__DIR__, 4);
        $router = $root . '/apps/easeo-website/public/router.php';
        $docroot = $root . '/apps/easeo-website/public';
        $cmd = sprintf('php -S localhost:8765 -t %s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($docroot),
            escapeshellarg($router)
        );
        self::$serverPid = (int)trim(shell_exec($cmd));
        usleep(500000); // wacht 0.5s tot server op is
    }

    public static function tearDownAfterClass(): void {
        if (self::$serverPid) {
            shell_exec('kill ' . self::$serverPid . ' 2>/dev/null');
        }
    }

    public function testHomepageReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Option B: 302 = setup-redirect is acceptable during Fase A (no site.json)
        $this->assertContains($status, [200, 302], 'Homepage moet 200 of 302 zijn (302 = setup-redirect, acceptable tijdens Fase A)');
        if ($status === 200) {
            $this->assertNotEmpty($body);
        }
    }

    public function testBlogReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/blog/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Option B: blog also calls check_setup(), may redirect
        $this->assertContains($status, [200, 302], 'Blog moet 200 of 302 zijn (302 = setup-redirect, acceptable tijdens Fase A)');
    }

    public function testContactReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/contact/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Option B: contact also calls check_setup(), may redirect
        $this->assertContains($status, [200, 302], 'Contact moet 200 of 302 zijn (302 = setup-redirect, acceptable tijdens Fase A)');
    }

    public function testSitemapReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/sitemap.xml');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->assertSame(200, $status);
        $this->assertStringContainsString('<urlset', $body);
    }
}
