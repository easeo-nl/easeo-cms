<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module;

use Easeo\Cms\Config\SiteConfig;
use Easeo\Cms\Module\InvalidModuleException;
use Easeo\Cms\Module\ModuleLoader;
use Easeo\Cms\Module\ModuleNotFoundException;
use Easeo\Cms\Module\Registry;
use PHPUnit\Framework\TestCase;

final class ModuleLoaderTest extends TestCase
{
    private string $tmpAppRoot = '';
    private string $tmpData    = '';
    private string $originalEaseoData = '';

    /** @var list<string> */
    private array $symlinksCreated = [];

    protected function setUp(): void
    {
        $this->tmpAppRoot = sys_get_temp_dir() . '/module-loader-test-' . uniqid('', true);
        $this->tmpData    = $this->tmpAppRoot . '/data';

        mkdir($this->tmpAppRoot . '/vendor/easeo', 0755, true);
        mkdir($this->tmpData, 0755, true);

        // Symlink fixtures into tmpAppRoot/vendor/easeo/<name>
        $fixtureBase = __DIR__ . '/Fixtures/loader';
        foreach (['good-module', 'no-class-module', 'invalid-json-module', 'not-implementing-module'] as $name) {
            $target = $fixtureBase . '/' . $name;
            $link   = $this->tmpAppRoot . '/vendor/easeo/' . $name;
            symlink($target, $link);
            $this->symlinksCreated[] = $link;
        }

        // Manually load fixture classes since they're not in composer autoload
        require_once __DIR__ . '/Fixtures/loader/good-module/src/Module.php';
        require_once __DIR__ . '/Fixtures/loader/not-implementing-module/src/Module.php';

        // Point SiteConfig at our temp data dir
        $this->originalEaseoData = (string)(getenv('EASEO_DATA') ?: '');
        putenv('EASEO_DATA=' . $this->tmpData);
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

        foreach ($this->symlinksCreated as $link) {
            if (is_link($link)) {
                unlink($link);
            }
        }

        if ($this->tmpAppRoot !== '' && is_dir($this->tmpAppRoot)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmpAppRoot));
        }
    }

    // -------------------------------------------------------------------------
    // loadFromConfig tests
    // -------------------------------------------------------------------------

    public function test_load_from_config_returns_empty_when_no_modules_listed(): void
    {
        // site.config.json exists but has no modules key
        file_put_contents($this->tmpData . '/site.config.json', json_encode(['brand' => ['name' => 'Test']]));
        SiteConfig::resetCache();

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        $this->assertSame([], $loaded);
        $this->assertSame(0, $registry->routes()->count());
    }

    public function test_load_from_config_with_single_good_module(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => ['easeo/good-module']])
        );
        SiteConfig::resetCache();

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        $this->assertSame(['easeo/good-module'], $loaded);
        // GoodModule registers /good route and test.fixture.fired hook
        $this->assertSame(1, $registry->routes()->count());
        $route = $registry->routes()->match('GET', '/good');
        $this->assertNotNull($route);
        $this->assertSame('easeo/good-module', $route['owner']);
    }

    public function test_load_from_config_skips_unknown_module_with_error_log(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => ['easeo/does-not-exist']])
        );
        SiteConfig::resetCache();

        $logFile = $this->tmpAppRoot . '/error.log';
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', $logFile);

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        ini_set('error_log', (string)$originalErrorLog);

        $this->assertSame([], $loaded);
        $this->assertSame(0, $registry->routes()->count());
        // Check error was logged
        $this->assertFileExists($logFile);
        $logContents = file_get_contents($logFile);
        $this->assertStringContainsString('easeo/does-not-exist', (string)$logContents);
    }

    public function test_load_from_config_skips_invalid_module_with_error_log(): void
    {
        // no-class-module has no extra.easeo.module-class
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => ['easeo/no-class-module']])
        );
        SiteConfig::resetCache();

        $logFile = $this->tmpAppRoot . '/error2.log';
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', $logFile);

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        ini_set('error_log', (string)$originalErrorLog);

        $this->assertSame([], $loaded);
        $this->assertFileExists($logFile);
        $logContents = file_get_contents($logFile);
        $this->assertStringContainsString('easeo/no-class-module', (string)$logContents);
    }

    public function test_load_from_config_skips_malformed_json_with_error_log(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => ['easeo/invalid-json-module']])
        );
        SiteConfig::resetCache();

        $logFile = $this->tmpAppRoot . '/error3.log';
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', $logFile);

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        ini_set('error_log', (string)$originalErrorLog);

        $this->assertSame([], $loaded);
        $this->assertFileExists($logFile);
        $logContents = file_get_contents($logFile);
        $this->assertStringContainsString('easeo/invalid-json-module', (string)$logContents);
    }

    public function test_load_from_config_loads_multiple_modules_and_skips_bad_ones(): void
    {
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => ['easeo/good-module', 'easeo/does-not-exist']])
        );
        SiteConfig::resetCache();

        $logFile = $this->tmpAppRoot . '/error4.log';
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', $logFile);

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        ini_set('error_log', (string)$originalErrorLog);

        // Only good-module succeeds
        $this->assertSame(['easeo/good-module'], $loaded);
        $this->assertSame(1, $registry->routes()->count());
    }

    // -------------------------------------------------------------------------
    // loadOrThrow tests
    // -------------------------------------------------------------------------

    public function test_load_or_throw_throws_module_not_found_for_unknown(): void
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessageMatches('/easeo\/does-not-exist/');

        $registry = new Registry();
        ModuleLoader::loadOrThrow($this->tmpAppRoot, 'easeo/does-not-exist', $registry);
    }

    public function test_load_or_throw_throws_invalid_module_for_no_class(): void
    {
        $this->expectException(InvalidModuleException::class);
        $this->expectExceptionMessageMatches('/extra\.easeo\.module-class/');

        $registry = new Registry();
        ModuleLoader::loadOrThrow($this->tmpAppRoot, 'easeo/no-class-module', $registry);
    }

    public function test_load_or_throw_throws_invalid_module_for_malformed_json(): void
    {
        $this->expectException(InvalidModuleException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');

        $registry = new Registry();
        ModuleLoader::loadOrThrow($this->tmpAppRoot, 'easeo/invalid-json-module', $registry);
    }

    public function test_load_or_throw_throws_invalid_module_for_non_implementing_class(): void
    {
        $this->expectException(InvalidModuleException::class);
        $this->expectExceptionMessageMatches('/does not implement Easeo\\\\Cms\\\\Module\\\\ModuleInterface/');

        $registry = new Registry();
        ModuleLoader::loadOrThrow($this->tmpAppRoot, 'easeo/not-implementing-module', $registry);
    }

    public function test_load_or_throw_succeeds_for_good_module(): void
    {
        $registry = new Registry();
        $module   = ModuleLoader::loadOrThrow($this->tmpAppRoot, 'easeo/good-module', $registry);

        $this->assertInstanceOf(\Easeo\Cms\Module\ModuleInterface::class, $module);
        $this->assertSame('easeo/good-module', $module->name());
        $this->assertSame(1, $registry->routes()->count());
    }

    public function test_load_from_config_returns_empty_when_modules_value_is_not_array(): void
    {
        // modules is a string instead of array — should handle gracefully
        file_put_contents(
            $this->tmpData . '/site.config.json',
            json_encode(['modules' => 'easeo/good-module'])
        );
        SiteConfig::resetCache();

        $registry = new Registry();
        $loaded   = ModuleLoader::loadFromConfig($this->tmpAppRoot, $registry);

        $this->assertSame([], $loaded);
    }
}
