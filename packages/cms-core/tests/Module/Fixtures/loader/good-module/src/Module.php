<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module\Fixtures\loader;

use Easeo\Cms\Module\ModuleInterface;
use Easeo\Cms\Module\Registry;

final class GoodModule implements ModuleInterface
{
    public function name(): string { return 'easeo/good-module'; }
    public function version(): string { return '0.0.1'; }
    public function register(Registry $r): void
    {
        $r->route('/good', 'GET', fn(): string => 'good-response', $this->name());
        $r->hook('test.fixture.fired', fn(): string => 'good-fired', $this->name());
    }
}
