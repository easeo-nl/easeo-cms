<?php
declare(strict_types=1);

namespace Easeo\Hello\Tests;

use Easeo\Cms\Module\Registry;
use Easeo\Hello\Module;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Easeo\Hello\Module.
 *
 * These tests do NOT require a full App::boot() — they work directly with a
 * fresh Registry instance. This keeps the hello-module test-suite fast and
 * decoupled from the app lifecycle.
 */
final class ModuleTest extends TestCase
{
    private Module $module;
    private Registry $registry;

    protected function setUp(): void
    {
        $this->module = new Module();
        $this->registry = new Registry();
        $this->module->register($this->registry);
    }

    public function test_name_returns_composer_package_name(): void
    {
        $this->assertSame('easeo/hello-module', $this->module->name());
    }

    public function test_version_is_semver_like(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+/',
            $this->module->version()
        );
    }

    public function test_register_adds_hello_route(): void
    {
        $route = $this->registry->routes()->match('GET', '/hello');

        $this->assertNotNull($route, 'Expected GET /hello route to be registered');
        $this->assertSame('GET', $route['method']);
        $this->assertSame('easeo/hello-module', $route['owner']);
        $this->assertIsCallable($route['handler']);
    }

    public function test_hello_route_handler_returns_hallo_wereld(): void
    {
        $route = $this->registry->routes()->match('GET', '/hello');
        $this->assertNotNull($route);

        $result = ($route['handler'])();

        $this->assertStringContainsString('Hallo wereld', $result);
    }

    public function test_register_adds_beheer_page_for_hello_tab(): void
    {
        $page = $this->registry->beheerPages()->get('hello');

        $this->assertNotNull($page, 'Expected beheer-page with tab "hello" to be registered');
        $this->assertSame('hello', $page['tab']);
        $this->assertSame('Hello', $page['label']);
        $this->assertSame('easeo/hello-module', $page['owner']);
        $this->assertFalse($page['admin_only']);
        $this->assertIsCallable($page['renderer']);
    }

    public function test_beheer_page_renderer_outputs_module_status(): void
    {
        $page = $this->registry->beheerPages()->get('hello');
        $this->assertNotNull($page);

        ob_start();
        ($page['renderer'])();
        $output = ob_get_clean();

        $this->assertStringContainsString('module werkt', $output);
        $this->assertStringContainsString('easeo/hello-module', $output);
    }

    public function test_register_subscribes_to_footer_hook(): void
    {
        $this->assertTrue(
            $this->registry->hooks()->hasSubscribers('cms.footer.before'),
            'Expected cms.footer.before hook to have subscribers'
        );
    }

    public function test_footer_hook_returns_html_comment(): void
    {
        $output = $this->registry->hooks()->fire('cms.footer.before');

        $this->assertStringContainsString('<!-- hello-module footer -->', $output);
    }

    public function test_route_count_is_exactly_one(): void
    {
        $this->assertSame(1, $this->registry->routes()->count());
    }
}
