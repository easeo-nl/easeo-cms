<?php
declare(strict_types=1);

namespace Easeo\Shop;

use Easeo\Cms\Module\ModuleInterface;
use Easeo\Cms\Module\Registry;

/**
 * Shop-module skelet. Registers only a placeholder beheer-tab.
 * Echte shop-logica (cart, checkout, orders) komt bij de eurolight-
 * webshop kickoff — zie `packages/shop-module/README.md`.
 */
final class Module implements ModuleInterface
{
    public function name(): string
    {
        return 'easeo/shop-module';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function register(Registry $r): void
    {
        $r->beheerPage(
            'shop',
            'Shop',
            // Shopping cart icon (Heroicons outline)
            'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            function (): string {
                return '<div class="admin-card">'
                    . '<h2 class="text-xl font-semibold mb-2">Shop module — skelet</h2>'
                    . '<p class="text-gray-400">Deze module is geparkeerd. Echte cart/checkout/orders logica komt bij de eurolight-webshop kickoff.</p>'
                    . '<p class="text-sm text-gray-500 mt-3">Payment-provider abstractie is wel beschikbaar: <code>Easeo\\Shop\\Payment\\PaymentProviderFactory</code>. Configureer <code>PAYMENT_PROVIDER</code> in .env.</p>'
                    . '</div>';
            },
            $this->name(),
            true // admin-only
        );
    }
}
