<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module\Fixtures;

use Easeo\Cms\Module\ModuleInterface;
use Easeo\Cms\Module\Registry;

final class DummyModule implements ModuleInterface
{
    public function name(): string
    {
        return 'easeo/dummy-module';
    }

    public function version(): string
    {
        return '0.0.1';
    }

    public function register(Registry $r): void
    {
        $r->route('/dummy', 'GET', fn(): string => 'dummy-response', $this->name());
        $r->beheerPage(
            'dummy',
            'Dummy',
            'M0 0',  // minimal SVG path-d
            fn(): string => '<p>dummy admin</p>',
            $this->name()
        );
        $r->hook('cms.footer.before', fn(): string => '<!-- dummy -->', $this->name());
        $r->configSchema('dummy.enabled', true, $this->name());
    }
}
