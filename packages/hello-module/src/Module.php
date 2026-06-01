<?php
declare(strict_types=1);

namespace Easeo\Hello;

use Easeo\Cms\Module\ModuleInterface;
use Easeo\Cms\Module\Registry;

/**
 * Hello-module — reference implementation of a cms-core module.
 *
 * Registers:
 *  - Route:       GET /hello → returns "Hallo wereld" HTML
 *  - Beheer-page: tab "hello", label "Hello", shows status info
 *  - Hook:        cms.footer.before → injects a HTML comment
 */
final class Module implements ModuleInterface
{
    public function name(): string
    {
        return 'easeo/hello-module';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function register(Registry $registry): void
    {
        // 1. Front-end route
        $registry->route(
            '/hello',
            'GET',
            fn() => $this->renderHello(),
            $this->name()
        );

        // 2. Admin beheer-page (non-admin-only — any logged-in user can view)
        $registry->beheerPage(
            'hello',
            'Hello',
            // Heroicon: check-circle (outline)
            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            fn() => $this->renderBeheerPage(),
            $this->name(),
            false
        );

        // 3. Hook subscriber
        $registry->hook(
            'cms.footer.before',
            fn() => '<!-- hello-module footer -->',
            $this->name()
        );
    }

    private function renderHello(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello — EASEO CMS</title>
</head>
<body>
    <h1>Hallo wereld</h1>
    <p>Hello-module is actief. Module: easeo/hello-module v0.1.0</p>
</body>
</html>
HTML;
    }

    private function renderBeheerPage(): void
    {
        echo <<<HTML
<div class="admin-card">
    <h2 class="text-xl font-semibold text-white mb-4">Hello-module</h2>
    <p class="text-green-400 font-medium">module werkt</p>
    <p class="text-gray-400 mt-2 text-sm">
        Module: <code class="text-blue-300">easeo/hello-module</code> v0.1.0<br>
        Route: <code class="text-blue-300">GET /hello</code> geregistreerd.<br>
        Hook: <code class="text-blue-300">cms.footer.before</code> subscriber actief.
    </p>
</div>
HTML;
    }
}
