# easeo/shop-module

EASEO CMS — Shop module skeleton.

> **Skelet status.** Echte shop-logica (cart, checkout, orders, product-catalog) komt bij de eurolight-webshop kickoff. Op dit moment registreert deze module alleen een geparkeerde beheer-tab en biedt een Payment-provider abstractie met stub adapters.

## Wat zit er nu wel in?

- `Easeo\Shop\Module` — registreert een admin-only beheer-tab "Shop" met een "geparkeerd" placeholder
- `Easeo\Shop\Payment\PaymentProvider` interface
- `Easeo\Shop\Payment\PaymentStatus` enum (Pending/Paid/Failed/Expired/Refunded/Cancelled)
- `Easeo\Shop\Payment\RefundResult` value-object
- `Easeo\Shop\Payment\PaymentProviderFactory::make()` — leest `PAYMENT_PROVIDER` env var en instantieert juiste adapter
- 3 stub adapters: `MollieProvider`, `StripeProvider`, `AdyenProvider` — alle methods werpen `NotImplementedException`

## Configuratie

In `.env`:

```
PAYMENT_PROVIDER=mollie    # of stripe / adyen
MOLLIE_API_KEY=test_xxx    # alleen vereist als PAYMENT_PROVIDER=mollie
STRIPE_SECRET_KEY=sk_xxx   # alleen vereist als PAYMENT_PROVIDER=stripe
ADYEN_API_KEY=AQE...       # alleen vereist als PAYMENT_PROVIDER=adyen
```

## Wat komt later (eurolight-kickoff)

- Echte adapter-implementaties (Mollie/Stripe/Adyen API-calls met signature verification)
- Cart engine + product-catalog
- Order-management + admin
- Webhook handlers per provider
- Email-notificaties + invoice-PDFs (via `easeo/cms-core` Mailer)
