<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment\Providers;

use Easeo\Shop\Payment\NotImplementedException;
use Easeo\Shop\Payment\PaymentProvider;
use Easeo\Shop\Payment\PaymentStatus;
use Easeo\Shop\Payment\RefundResult;

final class StripeProvider implements PaymentProvider
{
    public function __construct(private readonly string $apiKey) {}

    public function name(): string { return 'stripe'; }

    public function createPayment(int $amountCents, string $currency, string $description, string $webhookUrl, string $returnUrl, array $metadata = []): array
    {
        throw new NotImplementedException(self::class . '::createPayment');
    }

    public function verifyWebhook(array $headers, string $rawBody): PaymentStatus
    {
        throw new NotImplementedException(self::class . '::verifyWebhook');
    }

    public function fetchStatus(string $paymentId): PaymentStatus
    {
        throw new NotImplementedException(self::class . '::fetchStatus');
    }

    public function refund(string $paymentId, int $amountCents, ?string $reason = null): RefundResult
    {
        throw new NotImplementedException(self::class . '::refund');
    }
}
