<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module;

use Easeo\Cms\Module\Hook;
use PHPUnit\Framework\TestCase;

class HookTest extends TestCase
{
    public function testFireOnEmptyHookReturnsEmptyString(): void
    {
        $hook = new Hook();
        $this->assertSame('', $hook->fire('cms.footer.before'));
    }

    public function testSubscribeAndFireReturnsConcatenatedOutput(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.footer.before', fn() => '<!-- foo -->', 'mod-a');
        $hook->subscribe('cms.footer.before', fn() => '<!-- bar -->', 'mod-b');

        $output = $hook->fire('cms.footer.before');
        $this->assertSame('<!-- foo --><!-- bar -->', $output);
    }

    public function testMultipleSubscribersOutputInPriorityOrder(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.test', fn() => 'third', 'mod-c', 300);
        $hook->subscribe('cms.test', fn() => 'first', 'mod-a', 100);
        $hook->subscribe('cms.test', fn() => 'second', 'mod-b', 200);

        $output = $hook->fire('cms.test');
        $this->assertSame('firstsecondthird', $output);
    }

    public function testEqualPrioritiesPreserveRegistrationOrder(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.test', fn() => 'A', 'mod-a', 100);
        $hook->subscribe('cms.test', fn() => 'B', 'mod-b', 100);
        $hook->subscribe('cms.test', fn() => 'C', 'mod-c', 100);

        $output = $hook->fire('cms.test');
        $this->assertSame('ABC', $output);
    }

    public function testSubscriberReturningNullIsSkipped(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.test', fn() => null, 'mod-null');
        $hook->subscribe('cms.test', fn() => 'after-null', 'mod-after');

        $output = $hook->fire('cms.test');
        $this->assertSame('after-null', $output);
    }

    public function testThrowingSubscriberDoesNotPreventNextSubscriberFromRunning(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.test', function () {
            throw new \RuntimeException('kaboom');
        }, 'mod-thrower', 100);
        $hook->subscribe('cms.test', fn() => 'survived', 'mod-survivor', 200);

        // Redirect error_log to a temp file so the test output stays clean
        $tmpFile = tempnam(sys_get_temp_dir(), 'hook_test_');
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', $tmpFile);

        $output = $hook->fire('cms.test');

        ini_set('error_log', $originalErrorLog);

        $this->assertSame('survived', $output);

        // Verify error was logged
        $loggedContent = file_get_contents($tmpFile);
        unlink($tmpFile);
        $this->assertStringContainsString('kaboom', $loggedContent);
    }

    public function testHasSubscribersReportsTrueAfterSubscription(): void
    {
        $hook = new Hook();
        $this->assertFalse($hook->hasSubscribers('cms.footer.before'));

        $hook->subscribe('cms.footer.before', fn() => '', 'mod-a');
        $this->assertTrue($hook->hasSubscribers('cms.footer.before'));
    }

    public function testHasSubscribersReturnsFalseForOtherHooks(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.footer.before', fn() => '', 'mod-a');

        $this->assertFalse($hook->hasSubscribers('cms.head.end'));
    }

    public function testContextArrayPassedToSubscriber(): void
    {
        $hook = new Hook();
        $received = null;
        $hook->subscribe('cms.test', function (array $ctx) use (&$received): string {
            $received = $ctx;
            return 'ok';
        }, 'mod-ctx');

        $context = ['user_id' => 42, 'page' => 'home'];
        $hook->fire('cms.test', $context);

        $this->assertSame($context, $received);
    }

    public function testKnownHooksListsRegisteredHookNames(): void
    {
        $hook = new Hook();
        $this->assertSame([], $hook->knownHooks());

        $hook->subscribe('cms.footer.before', fn() => '', 'mod-a');
        $hook->subscribe('cms.head.end', fn() => '', 'mod-b');

        $known = $hook->knownHooks();
        $this->assertContains('cms.footer.before', $known);
        $this->assertContains('cms.head.end', $known);
        $this->assertCount(2, $known);
    }

    public function testNonStringReturnIsCoercedToString(): void
    {
        $hook = new Hook();
        $hook->subscribe('cms.test', fn() => 42, 'mod-int');

        $output = $hook->fire('cms.test');
        $this->assertSame('42', $output);
    }
}
