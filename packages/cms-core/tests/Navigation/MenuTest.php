<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Navigation;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Navigation\Menu;
use PHPUnit\Framework\TestCase;

final class MenuTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/menu-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
        ContentRepository::resetCache();
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        ContentRepository::resetCache();
        if (is_dir($this->tmpDataDir)) {
            foreach (glob($this->tmpDataDir . '/*') ?: [] as $f) {
                if (is_file($f)) unlink($f);
            }
            rmdir($this->tmpDataDir);
        }
    }

    public function testGetDynamicPageMenuItemsReturnsEmptyWhenNoPages(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode(['pages' => []], JSON_THROW_ON_ERROR)
        );
        $this->assertSame([], Menu::getDynamicPageMenuItems());
    }

    public function testGetDynamicPageMenuItemsFiltersUnpublishedPages(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode([
                'pages' => [
                    [
                        'id' => '1',
                        'slug' => 'published',
                        'title' => 'Published Page',
                        'menu_label' => 'Published',
                        'status' => 'published',
                        'show_in_menu' => true,
                        'sort_order' => 0,
                        'parent' => null,
                    ],
                    [
                        'id' => '2',
                        'slug' => 'draft',
                        'title' => 'Draft Page',
                        'menu_label' => 'Draft',
                        'status' => 'draft',
                        'show_in_menu' => true,
                        'sort_order' => 1,
                        'parent' => null,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)
        );
        $items = Menu::getDynamicPageMenuItems();
        $this->assertCount(1, $items);
        $this->assertSame('Published', $items[0]['label']);
    }

    public function testGetDynamicPageMenuItemsSupportsHierarchy(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode([
                'pages' => [
                    [
                        'id' => '1',
                        'slug' => 'parent',
                        'title' => 'Parent Page',
                        'menu_label' => 'Parent',
                        'status' => 'published',
                        'show_in_menu' => true,
                        'sort_order' => 0,
                        'parent' => null,
                    ],
                    [
                        'id' => '2',
                        'slug' => 'child',
                        'title' => 'Child Page',
                        'menu_label' => 'Child',
                        'status' => 'published',
                        'show_in_menu' => true,
                        'sort_order' => 0,
                        'parent' => '1',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)
        );
        $items = Menu::getDynamicPageMenuItems();
        $this->assertCount(1, $items);
        $this->assertSame('Parent', $items[0]['label']);
        $this->assertCount(1, $items[0]['children']);
        $this->assertSame('Child', $items[0]['children'][0]['label']);
    }

    public function testMergeNavWithDynamicPreservesManualItems(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode(['pages' => []], JSON_THROW_ON_ERROR)
        );
        $manual = [
            ['url' => '/', 'label' => 'Home'],
            ['url' => '/contact', 'label' => 'Contact'],
        ];
        $merged = Menu::mergeNavWithDynamic($manual);
        $this->assertIsArray($merged);
        $this->assertCount(2, $merged);
        $this->assertSame('Home', $merged[0]['label']);
        $this->assertSame('Contact', $merged[1]['label']);
    }

    public function testMergeNavWithDynamicAddsDynamicItemsNotInManual(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode([
                'pages' => [
                    [
                        'id' => '1',
                        'slug' => 'about',
                        'title' => 'About',
                        'menu_label' => 'About',
                        'status' => 'published',
                        'show_in_menu' => true,
                        'sort_order' => 0,
                        'parent' => null,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)
        );
        $manual = [
            ['url' => '/', 'label' => 'Home'],
        ];
        $merged = Menu::mergeNavWithDynamic($manual);
        $this->assertCount(2, $merged);
        $titles = array_column($merged, 'label');
        $this->assertContains('Home', $titles);
        $this->assertContains('About', $titles);
    }

    public function testMergeNavWithDynamicSkipsDuplicateUrls(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode([
                'pages' => [
                    [
                        'id' => '1',
                        'slug' => 'about',
                        'title' => 'About',
                        'menu_label' => 'About',
                        'status' => 'published',
                        'show_in_menu' => true,
                        'sort_order' => 0,
                        'parent' => null,
                    ],
                ],
            ], JSON_THROW_ON_ERROR)
        );
        $manual = [
            ['url' => '/', 'label' => 'Home'],
            ['url' => '/about', 'label' => 'About Us'],
        ];
        $merged = Menu::mergeNavWithDynamic($manual);
        $this->assertCount(2, $merged);
        // Should have Home and About Us (manual), not duplicate from dynamic
        $this->assertSame('About Us', $merged[1]['label']);
    }

    public function testRenderMainNavReturnsString(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/pages.json",
            json_encode(['pages' => []], JSON_THROW_ON_ERROR)
        );
        $GLOBALS['navigation'] = ['main' => []];
        $html = Menu::renderMainNav();
        $this->assertIsString($html);
        $this->assertStringContainsString('id="main-nav"', $html);
        $this->assertStringContainsString('class="hidden md:flex', $html);
    }

    public function testRenderMainNavEmitsCorrectMarkup(): void
    {
        file_put_contents("{$this->tmpDataDir}/pages.json", json_encode(['pages' => []], JSON_THROW_ON_ERROR));
        $GLOBALS['navigation'] = [
            'main' => [
                ['url' => '/', 'label' => 'Home'],
            ],
        ];
        $_SERVER['REQUEST_URI'] = '/';
        $html = Menu::renderMainNav();
        $this->assertStringContainsString('text-primary font-semibold', $html);
        $this->assertStringContainsString('href="/"', $html);
        $this->assertStringContainsString('>Home<', $html);
    }

    public function testRenderMainNavSupportsDropdowns(): void
    {
        file_put_contents("{$this->tmpDataDir}/pages.json", json_encode(['pages' => []], JSON_THROW_ON_ERROR));
        $GLOBALS['navigation'] = [
            'main' => [
                [
                    'url' => '/services',
                    'label' => 'Services',
                    'children' => [
                        ['url' => '/services/web', 'label' => 'Web Design'],
                    ],
                ],
            ],
        ];
        $_SERVER['REQUEST_URI'] = '/';
        $html = Menu::renderMainNav();
        $this->assertStringContainsString('relative group', $html);
        $this->assertStringContainsString('>Services <svg', $html);
        $this->assertStringContainsString('>Web Design<', $html);
    }

    public function testRenderMobileNavReturnsString(): void
    {
        file_put_contents("{$this->tmpDataDir}/pages.json", json_encode(['pages' => []], JSON_THROW_ON_ERROR));
        $GLOBALS['navigation'] = ['main' => []];
        $html = Menu::renderMobileNav();
        $this->assertIsString($html);
        $this->assertStringContainsString('id="mobile-menu"', $html);
        $this->assertStringContainsString('class="hidden md:hidden', $html);
    }

    public function testRenderMobileNavEmitsCorrectMarkup(): void
    {
        file_put_contents("{$this->tmpDataDir}/pages.json", json_encode(['pages' => []], JSON_THROW_ON_ERROR));
        $GLOBALS['navigation'] = [
            'main' => [
                ['url' => '/contact', 'label' => 'Contact'],
            ],
        ];
        $html = Menu::renderMobileNav();
        $this->assertStringContainsString('href="/contact"', $html);
        $this->assertStringContainsString('>Contact<', $html);
    }

    public function testRenderFooterNavReturnsString(): void
    {
        $GLOBALS['navigation'] = ['footer' => []];
        $html = Menu::renderFooterNav();
        $this->assertIsString($html);
    }

    public function testRenderFooterNavEmitsCorrectMarkup(): void
    {
        $GLOBALS['navigation'] = [
            'footer' => [
                ['url' => '/privacy', 'label' => 'Privacy'],
                ['url' => '/terms', 'label' => 'Terms'],
            ],
        ];
        $html = Menu::renderFooterNav();
        $this->assertStringContainsString('href="/privacy"', $html);
        $this->assertStringContainsString('>Privacy<', $html);
        $this->assertStringContainsString('href="/terms"', $html);
        $this->assertStringContainsString('>Terms<', $html);
    }
}
