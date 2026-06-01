<?php
declare(strict_types=1);

namespace Easeo\Shop\Tests\Module;

use Easeo\Cms\Module\Registry;
use Easeo\Shop\Module;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{
    public function test_name_and_version(): void
    {
        $m = new Module();
        $this->assertSame('easeo/shop-module', $m->name());
        $this->assertNotEmpty($m->version());
    }

    public function test_register_adds_shop_beheer_tab_only(): void
    {
        $r = new Registry();
        (new Module())->register($r);

        $pages = $r->beheerPages()->all();
        $this->assertArrayHasKey('shop', $pages);
        $this->assertTrue($pages['shop']['admin_only']);
        $this->assertSame(0, $r->routes()->count(), 'shop-module registers no public routes (skelet)');
    }

    public function test_renderer_emits_skeleton_card(): void
    {
        $r = new Registry();
        (new Module())->register($r);
        $page = $r->beheerPages()->get('shop');
        $html = ($page['renderer'])();
        $this->assertStringContainsString('geparkeerd', $html);
        $this->assertStringContainsString('PaymentProviderFactory', $html);
    }
}
