<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Module\Fixtures\loader;

/**
 * Intentionally does NOT implement ModuleInterface — used to test that
 * the loader throws InvalidModuleException for non-conforming classes.
 */
final class NotImplementingModule
{
    public function name(): string { return 'easeo/not-implementing-module'; }
}
