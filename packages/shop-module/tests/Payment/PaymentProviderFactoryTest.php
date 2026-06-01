<?php
declare(strict_types=1);

namespace Easeo\Shop\Tests\Payment;

use Easeo\Cms\Config\Environment;
use Easeo\Shop\Payment\PaymentProviderFactory;
use Easeo\Shop\Payment\Providers\AdyenProvider;
use Easeo\Shop\Payment\Providers\MollieProvider;
use Easeo\Shop\Payment\Providers\StripeProvider;
use PHPUnit\Framework\TestCase;

final class PaymentProviderFactoryTest extends TestCase
{
    /** @var array<string,string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach (['PAYMENT_PROVIDER', 'MOLLIE_API_KEY', 'STRIPE_SECRET_KEY', 'ADYEN_API_KEY'] as $k) {
            $this->savedEnv[$k] = getenv($k);
            putenv($k);
        }
        Environment::reset();
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $k => $v) {
            $v === false ? putenv($k) : putenv("$k=$v");
        }
        Environment::reset();
    }

    public function test_make_throws_when_provider_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('PAYMENT_PROVIDER');
        PaymentProviderFactory::make();
    }

    public function test_make_throws_for_unknown_provider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('paypal');
        PaymentProviderFactory::make('paypal');
    }

    public function test_make_throws_when_api_key_missing(): void
    {
        putenv('PAYMENT_PROVIDER=mollie');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MOLLIE_API_KEY');
        PaymentProviderFactory::make();
    }

    public function test_make_mollie(): void
    {
        putenv('PAYMENT_PROVIDER=mollie');
        putenv('MOLLIE_API_KEY=test_xxx');
        $p = PaymentProviderFactory::make();
        $this->assertInstanceOf(MollieProvider::class, $p);
        $this->assertSame('mollie', $p->name());
    }

    public function test_make_stripe(): void
    {
        putenv('PAYMENT_PROVIDER=stripe');
        putenv('STRIPE_SECRET_KEY=sk_test_x');
        $this->assertInstanceOf(StripeProvider::class, PaymentProviderFactory::make());
    }

    public function test_make_adyen(): void
    {
        putenv('PAYMENT_PROVIDER=adyen');
        putenv('ADYEN_API_KEY=AQE...');
        $this->assertInstanceOf(AdyenProvider::class, PaymentProviderFactory::make());
    }

    public function test_make_uppercase_provider_name_works(): void
    {
        putenv('PAYMENT_PROVIDER=MOLLIE');
        putenv('MOLLIE_API_KEY=test');
        $this->assertInstanceOf(MollieProvider::class, PaymentProviderFactory::make());
    }

    public function test_known_providers_list(): void
    {
        $this->assertSame(['mollie', 'stripe', 'adyen'], PaymentProviderFactory::knownProviders());
    }
}
