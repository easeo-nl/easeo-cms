<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests;

use Easeo\Cms\App;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    public function test_boot_returns_app_instance(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            $app = App::boot($appRoot);
            $this->assertSame($appRoot, $app->appRoot());
        } finally {
            @rmdir($appRoot);
        }
    }

    public function test_boot_strips_trailing_slash_from_app_root(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-slash-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            $app = App::boot($appRoot . '/');
            $this->assertSame($appRoot, $app->appRoot());
        } finally {
            @rmdir($appRoot);
        }
    }

    public function test_boot_defines_easeo_constants(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-const-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            App::boot($appRoot);
            // Constants::bootstrap is idempotent; if previous tests defined them
            // we just assert they're present, not their value.
            $this->assertTrue(defined('EASEO_APP'));
            $this->assertTrue(defined('EASEO_DATA'));
            $this->assertTrue(defined('EASEO_CORE'));
            $this->assertTrue(defined('EASEO_TEMPLATES'));
        } finally {
            @rmdir($appRoot);
        }
    }

    public function test_run_returns_void_no_op_by_default(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-run-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            $app = App::boot($appRoot);
            // run() should not throw and should not emit output
            ob_start();
            $app->run();
            $output = ob_get_clean();
            $this->assertSame('', $output);
        } finally {
            @rmdir($appRoot);
        }
    }

    public function test_registry_returns_empty_registry_by_default(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-registry-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            $app = App::boot($appRoot);
            $registry = $app->registry();
            $this->assertInstanceOf(\Easeo\Cms\Module\Registry::class, $registry);
            // No modules in site.config.json → no routes registered
            $this->assertSame(0, $registry->routes()->count());
        } finally {
            @rmdir($appRoot);
        }
    }

    public function test_loaded_modules_returns_empty_list_by_default(): void
    {
        $appRoot = sys_get_temp_dir() . '/app-test-modules-' . uniqid('', true);
        mkdir($appRoot, 0755, true);

        try {
            $app = App::boot($appRoot);
            $this->assertSame([], $app->loadedModules());
        } finally {
            @rmdir($appRoot);
        }
    }
}
