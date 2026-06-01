<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module;

use Easeo\Cms\Module\Registry;
use Easeo\Cms\Tests\Module\Fixtures\DummyModule;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    public function testEmptyRegistryHasNoRoutes(): void
    {
        $registry = new Registry();
        $this->assertSame(0, $registry->routes()->count());
    }

    public function testEmptyRegistryHasNoBeheerPages(): void
    {
        $registry = new Registry();
        $this->assertSame([], $registry->beheerPages()->all());
    }

    public function testEmptyRegistryHasNoConfigSchema(): void
    {
        $registry = new Registry();
        $this->assertSame([], $registry->configSchemaAll());
    }

    public function testEmptyRegistryHasNoHookSubscribers(): void
    {
        $registry = new Registry();
        $this->assertFalse($registry->hooks()->hasSubscribers('cms.footer.before'));
    }

    public function testDummyModuleRegistersOneRoute(): void
    {
        $registry = new Registry();
        $module = new DummyModule();
        $module->register($registry);

        $this->assertSame(1, $registry->routes()->count());
    }

    public function testDummyModuleRegistersOneBeheerPage(): void
    {
        $registry = new Registry();
        $module = new DummyModule();
        $module->register($registry);

        $pages = $registry->beheerPages()->all();
        $this->assertCount(1, $pages);
        $this->assertArrayHasKey('dummy', $pages);
    }

    public function testDummyModuleRegistersOneHook(): void
    {
        $registry = new Registry();
        $module = new DummyModule();
        $module->register($registry);

        $this->assertTrue($registry->hooks()->hasSubscribers('cms.footer.before'));
    }

    public function testDummyModuleRegistersOneConfigSchemaKey(): void
    {
        $registry = new Registry();
        $module = new DummyModule();
        $module->register($registry);

        $schema = $registry->configSchemaAll();
        $this->assertArrayHasKey('dummy.enabled', $schema);
        $this->assertTrue($schema['dummy.enabled']['default']);
    }

    public function testAllExtensionPointsOwnedByDummyModule(): void
    {
        $registry = new Registry();
        $module = new DummyModule();
        $module->register($registry);

        $routes = $registry->routes()->all();
        $route = reset($routes);
        $this->assertSame('easeo/dummy-module', $route['owner']);

        $page = $registry->beheerPages()->get('dummy');
        $this->assertSame('easeo/dummy-module', $page['owner']);

        $schema = $registry->configSchemaAll();
        $this->assertSame('easeo/dummy-module', $schema['dummy.enabled']['owner']);
    }

    public function testRouteMethodIsAccessibleViaRoutesFacade(): void
    {
        $registry = new Registry();
        $registry->route('/test', 'POST', fn() => 'ok', 'easeo/test-module');

        $all = $registry->routes()->all();
        $this->assertArrayHasKey('POST /test', $all);
        $this->assertSame('POST', $all['POST /test']['method']);
    }
}
