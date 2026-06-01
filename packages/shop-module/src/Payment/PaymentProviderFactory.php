<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment;

use Easeo\Cms\Config\Environment;
use Easeo\Shop\Payment\Providers\AdyenProvider;
use Easeo\Shop\Payment\Providers\MollieProvider;
use Easeo\Shop\Payment\Providers\StripeProvider;

final class PaymentProviderFactory
{
    private const PROVIDERS = ['mollie', 'stripe', 'adyen'];

    public static function make(?string $providerName = null): PaymentProvider
    {
        $name = $providerName ?? Environment::get('PAYMENT_PROVIDER', '');
        if ($name === null || $name === '') {
            throw new \RuntimeException(
                "PAYMENT_PROVIDER is niet ingesteld in .env. Geldig: " . implode(', ', self::PROVIDERS)
            );
        }
        $name = strtolower($name);

        return match ($name) {
            'mollie' => new MollieProvider(self::requireKey('MOLLIE_API_KEY')),
            'stripe' => new StripeProvider(self::requireKey('STRIPE_SECRET_KEY')),
            'adyen'  => new AdyenProvider(self::requireKey('ADYEN_API_KEY')),
            default  => throw new \InvalidArgumentException(
                "Onbekende PAYMENT_PROVIDER '$name'. Geldig: " . implode(', ', self::PROVIDERS)
            ),
        };
    }

    public static function knownProviders(): array
    {
        return self::PROVIDERS;
    }

    private static function requireKey(string $envVar): string
    {
        $value = Environment::get($envVar, '');
        if ($value === null || $value === '') {
            throw new \RuntimeException(
                "Payment provider vereist $envVar in .env (niet ingesteld of leeg)"
            );
        }
        return $value;
    }
}
