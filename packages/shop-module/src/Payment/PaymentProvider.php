<?php
declare(strict_types=1);

namespace Easeo\Shop\Payment;

/**
 * Contract for payment providers. Implementations live under
 * src/Payment/Providers/ (MollieProvider, StripeProvider, AdyenProvider).
 *
 * Skeleton implementations throw NotImplementedException — real logic
 * comes with the eurolight-webshop project.
 *
 * All amounts are in cents (int) to avoid float arithmetic on money.
 * All identifiers are provider-side strings (Mollie's payment id,
 * Stripe's PaymentIntent id, etc.) — the shop-module wraps them in
 * its own order-id mapping (later batch).
 */
interface PaymentProvider
{
    public function name(): string;

    /**
     * Create a new payment. Returns the provider's checkout URL the
     * customer should be redirected to.
     *
     * @param array<string,mixed> $metadata Provider-side metadata (order-id, customer-id, etc.)
     * @return array{paymentId:string, checkoutUrl:string}
     */
    public function createPayment(int $amountCents, string $currency, string $description, string $webhookUrl, string $returnUrl, array $metadata = []): array;

    /**
     * Verify a webhook delivery. Providers send a POST with an id; this
     * method confirms authenticity (signature check) and fetches the
     * current status from the provider's API.
     */
    public function verifyWebhook(array $headers, string $rawBody): PaymentStatus;

    /**
     * Fetch the current status of an existing payment.
     */
    public function fetchStatus(string $paymentId): PaymentStatus;

    /**
     * Refund a (partially or fully) paid payment.
     */
    public function refund(string $paymentId, int $amountCents, ?string $reason = null): RefundResult;
}
