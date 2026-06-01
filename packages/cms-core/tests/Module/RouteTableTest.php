<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module;

use Easeo\Cms\Module\DuplicateRouteException;
use Easeo\Cms\Module\RouteTable;
use PHPUnit\Framework\TestCase;

class RouteTableTest extends TestCase
{
    public function testAddAndRetrieveViaAll(): void
    {
        $table = new RouteTable();
        $handler = fn(): string => 'hello';
        $table->add('/hello', 'GET', $handler, 'easeo/hello-module');

        $all = $table->all();
        $this->assertArrayHasKey('GET /hello', $all);
        $this->assertSame('GET', $all['GET /hello']['method']);
        $this->assertSame('easeo/hello-module', $all['GET /hello']['owner']);
        $this->assertSame($handler, $all['GET /hello']['handler']);
    }

    public function testCountReflectsNumberOfRoutes(): void
    {
        $table = new RouteTable();
        $this->assertSame(0, $table->count());

        $table->add('/a', 'GET', fn() => 'a', 'mod-a');
        $this->assertSame(1, $table->count());

        $table->add('/b', 'POST', fn() => 'b', 'mod-b');
        $this->assertSame(2, $table->count());
    }

    public function testMatchReturnsRouteForExactMethodAndPath(): void
    {
        $table = new RouteTable();
        $handler = fn(): string => 'dummy-response';
        $table->add('/dummy', 'GET', $handler, 'easeo/dummy-module');

        $match = $table->match('GET', '/dummy');
        $this->assertNotNull($match);
        $this->assertSame('GET', $match['method']);
        $this->assertSame('easeo/dummy-module', $match['owner']);
    }

    public function testMatchReturnsNullForUnknownPath(): void
    {
        $table = new RouteTable();
        $table->add('/dummy', 'GET', fn() => '', 'easeo/dummy-module');

        $this->assertNull($table->match('GET', '/nope'));
    }

    public function testMatchReturnsNullForWrongMethod(): void
    {
        $table = new RouteTable();
        $table->add('/dummy', 'GET', fn() => '', 'easeo/dummy-module');

        $this->assertNull($table->match('POST', '/dummy'));
    }

    public function testDuplicatePatternAndMethodThrowsException(): void
    {
        $table = new RouteTable();
        $table->add('/hello', 'GET', fn() => 'first', 'easeo/first-module');

        $this->expectException(DuplicateRouteException::class);
        $this->expectExceptionMessageMatches('/easeo\/first-module/');
        $this->expectExceptionMessageMatches('/easeo\/second-module/');

        $table->add('/hello', 'GET', fn() => 'second', 'easeo/second-module');
    }

    public function testAnyMethodIsMatchedByAnyHttpVerb(): void
    {
        $table = new RouteTable();
        $table->add('/wildcard', 'ANY', fn() => 'any', 'easeo/wildcard-module');

        $this->assertNotNull($table->match('GET', '/wildcard'));
        $this->assertNotNull($table->match('POST', '/wildcard'));
        $this->assertNotNull($table->match('DELETE', '/wildcard'));
    }

    public function testSamePathDifferentMethodsAreAllowed(): void
    {
        $table = new RouteTable();
        $table->add('/resource', 'GET', fn() => 'get', 'mod-a');
        $table->add('/resource', 'POST', fn() => 'post', 'mod-b');

        $this->assertSame(2, $table->count());
        $this->assertNotNull($table->match('GET', '/resource'));
        $this->assertNotNull($table->match('POST', '/resource'));
    }

    public function testMethodIsNormalisedToUppercase(): void
    {
        $table = new RouteTable();
        $table->add('/lower', 'get', fn() => 'ok', 'mod');

        $all = $table->all();
        $this->assertArrayHasKey('GET /lower', $all);
    }
}
