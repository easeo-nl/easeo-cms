<?php
declare(strict_types=1);

namespace Easeo\Shop\Tests\Payment;

use Easeo\Shop\Payment\NotImplementedException;
use Easeo\Shop\Payment\Providers\AdyenProvider;
use Easeo\Shop\Payment\Providers\MollieProvider;
use Easeo\Shop\Payment\Providers\StripeProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StubProvidersTest extends TestCase
{
    public static function provider_classes(): array
    {
        return [
            'mollie' => [MollieProvider::class, 'mollie'],
            'stripe' => [StripeProvider::class, 'stripe'],
            'adyen'  => [AdyenProvider::class, 'adyen'],
        ];
    }

    #[DataProvider('provider_classes')]
    public function test_provider_name_matches(string $class, string $expectedName): void
    {
        $p = new $class('fake-api-key');
        $this->assertSame($expectedName, $p->name());
    }

    #[DataProvider('provider_classes')]
    public function test_create_payment_throws_not_implemented(string $class): void
    {
        $this->expectException(NotImplementedException::class);
        (new $class('fake'))->createPayment(1000, 'EUR', 'test', 'https://x/webhook', 'https://x/return');
    }

    #[DataProvider('provider_classes')]
    public function test_verify_webhook_throws(string $class): void
    {
        $this->expectException(NotImplementedException::class);
        (new $class('fake'))->verifyWebhook([], '');
    }

    #[DataProvider('provider_classes')]
    public function test_fetch_status_throws(string $class): void
    {
        $this->expectException(NotImplementedException::class);
        (new $class('fake'))->fetchStatus('pmt-x');
    }

    #[DataProvider('provider_classes')]
    public function test_refund_throws(string $class): void
    {
        $this->expectException(NotImplementedException::class);
        (new $class('fake'))->refund('pmt-x', 500);
    }
}
