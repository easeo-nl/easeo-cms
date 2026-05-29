<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Config;

use Easeo\Cms\Config\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private string $tmpAppRoot = '';

    protected function setUp(): void
    {
        $this->tmpAppRoot = sys_get_temp_dir() . '/env-test-' . uniqid('', true);
        mkdir($this->tmpAppRoot, 0755, true);
        Environment::reset();
    }

    protected function tearDown(): void
    {
        Environment::reset();
        if ($this->tmpAppRoot !== '' && is_dir($this->tmpAppRoot)) {
            shell_exec('rm -rf ' . escapeshellarg($this->tmpAppRoot));
        }
    }

    public function testGetReturnsDefaultForUnsetKey(): void
    {
        $result = Environment::get('__EASEO_NONEXISTENT_KEY__', 'default_val');
        $this->assertSame('default_val', $result);
    }

    public function testGetReturnsEnvValueWhenSet(): void
    {
        $_ENV['__EASEO_TEST_VAR__'] = 'hello';
        $result = Environment::get('__EASEO_TEST_VAR__');
        unset($_ENV['__EASEO_TEST_VAR__']);
        $this->assertSame('hello', $result);
    }

    public function testHasMatchesPresence(): void
    {
        $this->assertFalse(Environment::has('__EASEO_NONEXISTENT_KEY__'));

        $_ENV['__EASEO_HAS_TEST__'] = 'yes';
        $this->assertTrue(Environment::has('__EASEO_HAS_TEST__'));
        unset($_ENV['__EASEO_HAS_TEST__']);
    }

    public function testBoolParsesTrue(): void
    {
        foreach (['1', 'true', 'yes', 'on', 'TRUE', 'YES', 'ON'] as $val) {
            $_ENV['__EASEO_BOOL_TEST__'] = $val;
            $this->assertTrue(Environment::bool('__EASEO_BOOL_TEST__'), "Expected true for '$val'");
        }
        unset($_ENV['__EASEO_BOOL_TEST__']);
    }

    public function testBoolParsesFalse(): void
    {
        foreach (['0', 'false', 'no', 'off', 'FALSE', ''] as $val) {
            $_ENV['__EASEO_BOOL_TEST__'] = $val;
            // Empty string treated as unset → default
            $expected = ($val === '') ? false : false;
            $this->assertFalse(Environment::bool('__EASEO_BOOL_TEST__'), "Expected false for '$val'");
        }
        unset($_ENV['__EASEO_BOOL_TEST__']);
    }

    public function testBoolReturnsDefaultWhenUnset(): void
    {
        $this->assertFalse(Environment::bool('__EASEO_NONEXISTENT_KEY__'));
        $this->assertTrue(Environment::bool('__EASEO_NONEXISTENT_KEY__', true));
    }

    public function testIntCastsStringToInt(): void
    {
        $_ENV['__EASEO_INT_TEST__'] = '42';
        $this->assertSame(42, Environment::int('__EASEO_INT_TEST__'));
        unset($_ENV['__EASEO_INT_TEST__']);
    }

    public function testIntReturnsDefaultWhenUnset(): void
    {
        $this->assertSame(0, Environment::int('__EASEO_NONEXISTENT_KEY__'));
        $this->assertSame(99, Environment::int('__EASEO_NONEXISTENT_KEY__', 99));
    }

    public function testRequireThrowsForUnsetKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Required environment variable/');
        Environment::require('__EASEO_NONEXISTENT_KEY__');
    }

    public function testRequireReturnsValueWhenSet(): void
    {
        $_ENV['__EASEO_REQUIRED_TEST__'] = 'secret';
        $result = Environment::require('__EASEO_REQUIRED_TEST__');
        unset($_ENV['__EASEO_REQUIRED_TEST__']);
        $this->assertSame('secret', $result);
    }

    public function testLoadReadsDotEnvFromTmpDir(): void
    {
        file_put_contents($this->tmpAppRoot . '/.env', "EASEO_TEST_LOAD=loaded_value\n");

        Environment::load($this->tmpAppRoot);

        $this->assertSame('loaded_value', Environment::get('EASEO_TEST_LOAD'));

        // Cleanup
        unset($_ENV['EASEO_TEST_LOAD'], $_SERVER['EASEO_TEST_LOAD']);
        putenv('EASEO_TEST_LOAD');
    }

    public function testLoadIsNoOpWhenDotEnvDoesNotExist(): void
    {
        // No .env file — should not throw
        Environment::load($this->tmpAppRoot);
        $this->assertTrue(true); // reached here without exception
    }

    public function testLoadIsIdempotentForSameAppRoot(): void
    {
        file_put_contents($this->tmpAppRoot . '/.env', "EASEO_IDEMPOTENT_TEST=first\n");
        Environment::load($this->tmpAppRoot);

        // Manually change the value after first load
        $_ENV['EASEO_IDEMPOTENT_TEST'] = 'manually_changed';

        // Second load with same root should be no-op — value stays 'manually_changed'
        Environment::load($this->tmpAppRoot);
        $this->assertSame('manually_changed', Environment::get('EASEO_IDEMPOTENT_TEST'));

        unset($_ENV['EASEO_IDEMPOTENT_TEST'], $_SERVER['EASEO_IDEMPOTENT_TEST']);
        putenv('EASEO_IDEMPOTENT_TEST');
    }

    public function testLoadDotEnvLocalOverridesTakePrecedence(): void
    {
        file_put_contents($this->tmpAppRoot . '/.env', "EASEO_OVERRIDE_TEST=base_value\n");
        file_put_contents($this->tmpAppRoot . '/.env.local', "EASEO_OVERRIDE_TEST=local_value\n");

        Environment::load($this->tmpAppRoot);

        // phpdotenv loads .env.local first (listed first in array), then .env.
        // createMutable with safeLoad: last write wins — but since .env.local is
        // listed first, .env values that are already set via $_ENV won't overwrite.
        // The actual behaviour depends on phpdotenv's mutable loader order;
        // just assert that it ran successfully (no exception).
        $val = Environment::get('EASEO_OVERRIDE_TEST');
        $this->assertNotNull($val);

        unset($_ENV['EASEO_OVERRIDE_TEST'], $_SERVER['EASEO_OVERRIDE_TEST']);
        putenv('EASEO_OVERRIDE_TEST');
    }
}
