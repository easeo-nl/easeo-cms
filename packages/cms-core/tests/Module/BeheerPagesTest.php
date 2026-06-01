<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module;

use Easeo\Cms\Module\BeheerPages;
use Easeo\Cms\Module\DuplicateBeheerPageException;
use PHPUnit\Framework\TestCase;

class BeheerPagesTest extends TestCase
{
    private function makePages(): BeheerPages
    {
        return new BeheerPages();
    }

    public function testAddAndRetrieveViaAll(): void
    {
        $pages = $this->makePages();
        $renderer = fn(): string => '<p>hello admin</p>';
        $pages->add('hello', 'Hello', 'M0 0', $renderer, 'easeo/hello-module');

        $all = $pages->all();
        $this->assertArrayHasKey('hello', $all);
        $this->assertSame('Hello', $all['hello']['label']);
        $this->assertSame('easeo/hello-module', $all['hello']['owner']);
        $this->assertSame($renderer, $all['hello']['renderer']);
        $this->assertSame('M0 0', $all['hello']['icon']);
    }

    public function testGetReturnsEntryForKnownTab(): void
    {
        $pages = $this->makePages();
        $pages->add('dummy', 'Dummy', 'M0 0', fn() => '', 'easeo/dummy-module');

        $entry = $pages->get('dummy');
        $this->assertNotNull($entry);
        $this->assertSame('dummy', $entry['tab']);
    }

    public function testGetReturnsNullForUnknownTab(): void
    {
        $pages = $this->makePages();
        $this->assertNull($pages->get('nope'));
    }

    public function testInvalidTabSlugUppercaseThrowsInvalidArgument(): void
    {
        $pages = $this->makePages();
        $this->expectException(\InvalidArgumentException::class);
        $pages->add('Hello', 'Hello', 'M0 0', fn() => '', 'easeo/mod');
    }

    public function testInvalidTabSlugWithSpaceThrowsInvalidArgument(): void
    {
        $pages = $this->makePages();
        $this->expectException(\InvalidArgumentException::class);
        $pages->add('my page', 'My Page', 'M0 0', fn() => '', 'easeo/mod');
    }

    public function testInvalidTabSlugWithDotThrowsInvalidArgument(): void
    {
        $pages = $this->makePages();
        $this->expectException(\InvalidArgumentException::class);
        $pages->add('my.page', 'My Page', 'M0 0', fn() => '', 'easeo/mod');
    }

    public function testDuplicateTabThrowsDuplicateBeheerPageException(): void
    {
        $pages = $this->makePages();
        $pages->add('hello', 'Hello', 'M0 0', fn() => '', 'easeo/first-module');

        $this->expectException(DuplicateBeheerPageException::class);
        $this->expectExceptionMessageMatches('/easeo\/first-module/');
        $this->expectExceptionMessageMatches('/easeo\/second-module/');

        $pages->add('hello', 'Hello Again', 'M0 0', fn() => '', 'easeo/second-module');
    }

    public function testAdminOnlyFlagStoredAsFalseByDefault(): void
    {
        $pages = $this->makePages();
        $pages->add('public-tab', 'Public', 'M0 0', fn() => '', 'easeo/mod');

        $entry = $pages->get('public-tab');
        $this->assertFalse($entry['admin_only']);
    }

    public function testAdminOnlyFlagStoredAsTrueWhenSet(): void
    {
        $pages = $this->makePages();
        $pages->add('admin-tab', 'Admin Only', 'M0 0', fn() => '', 'easeo/mod', true);

        $entry = $pages->get('admin-tab');
        $this->assertTrue($entry['admin_only']);
    }

    public function testKebabCaseWithNumbersIsValid(): void
    {
        $pages = $this->makePages();
        // Should not throw
        $pages->add('my-page-2', 'My Page 2', 'M0 0', fn() => '', 'easeo/mod');
        $this->assertNotNull($pages->get('my-page-2'));
    }
}
