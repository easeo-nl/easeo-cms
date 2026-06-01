<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment;

final readonly class RefundResult
{
    public function __construct(
        public string $refundId,
        public string $paymentId,
        public int $amountCents,
        public string $currency,
        public PaymentStatus $status,
        public ?string $reason = null,
    ) {}
}
