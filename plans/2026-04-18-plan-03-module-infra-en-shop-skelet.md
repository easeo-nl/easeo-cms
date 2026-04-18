# Plan 03 — Module-infra + hello-module + shop-skelet (Fase D)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 02 afgerond is (module-registry heeft toegang tot `SiteConfig` + `Environment` nodig).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bouw een generiek module-systeem in `cms-core` waarmee externe packages (`packages/hello-module`, `packages/shop-module`) zichzelf kunnen registreren via `ModuleInterface`. Ship een hello-module als smoke-test en een leeg shop-module-skelet met payment-provider-abstractie (Mollie, Stripe, Adyen stubs).

**Architectuur:** `ModuleInterface` definieert statische `register(Registry $r)`. `Registry` verzamelt routes, beheer-pagina's, hooks en config-schemas. Bootstrap leest `site.config.json.modules[]`, vindt `extra.easeo.module-class` in elk package's `composer.json`, en roept `register()` aan. Payment-provider is een aparte abstractie in `shop-module` met factory die op `.env` `PAYMENT_PROVIDER` matcht.

**Tech Stack:** PHP 8.1+, PSR-4 autoloading, bestaande `Environment` + `SiteConfig` uit Plan 02.

**Afhankelijkheden:** Plan 01 + Plan 02 moeten compleet zijn.

---

## Bestandsstructuur

**Aangemaakt — module-infra in cms-core:**
- `packages/cms-core/src/Module/ModuleInterface.php` — contract voor modules
- `packages/cms-core/src/Module/Registry.php` — verzamelt extension points
- `packages/cms-core/src/Module/ModuleLoader.php` — bootstrap: leest config, vindt modules, roept register()
- `packages/cms-core/src/Module/Hook.php` — named hook fire + subscribe
- `packages/cms-core/src/Module/RouteTable.php` — verzamelt route-patterns (merge met core-routes)
- `packages/cms-core/src/Module/BeheerPages.php` — registratie van admin-menu items
- `packages/cms-core/tests/Module/*` — unit tests per bovenstaande class
- `packages/cms-core/tests/Module/Fixtures/DummyModule.php` — test-fixture die alle 4 extension points gebruikt

**Aangemaakt — hello-module package:**
- `packages/hello-module/composer.json` — `name: easeo/hello-module`, PSR-4 `Easeo\Hello\`, `extra.easeo.module-class`
- `packages/hello-module/src/Module.php` — registreert `/hello` route + beheer-pagina + footer-hook
- `packages/hello-module/src/Controller/HelloController.php` — rendert "Hallo wereld"
- `packages/hello-module/src/Beheer/HelloPage.php` — simpele admin-pagina die "werkt" toont
- `packages/hello-module/tests/ModuleTest.php` — test dat `register()` alle 4 punten raakt
- `packages/hello-module/phpunit.xml`

**Aangemaakt — shop-module package (skelet):**
- `packages/shop-module/composer.json` — `name: easeo/shop-module`, PSR-4 `Easeo\Shop\`
- `packages/shop-module/src/Module.php` — registreert niks behalve leeg beheer-menu-item (placeholder)
- `packages/shop-module/src/Payment/PaymentProvider.php` — interface
- `packages/shop-module/src/Payment/PaymentStatus.php` — enum (Pending, Paid, Failed, Expired, Refunded)
- `packages/shop-module/src/Payment/RefundResult.php` — value-object
- `packages/shop-module/src/Payment/NotImplementedException.php`
- `packages/shop-module/src/Payment/PaymentProviderFactory.php` — leest `PAYMENT_PROVIDER` env + instantieert adapter
- `packages/shop-module/src/Payment/Providers/MollieProvider.php` — stub, alle methods `throw new NotImplementedException()`
- `packages/shop-module/src/Payment/Providers/StripeProvider.php` — stub
- `packages/shop-module/src/Payment/Providers/AdyenProvider.php` — stub
- `packages/shop-module/tests/Payment/PaymentProviderFactoryTest.php`
- `packages/shop-module/tests/Module/ModuleTest.php`
- `packages/shop-module/phpunit.xml`

**Gewijzigd:**
- `apps/easeo-website/composer.json` — add `easeo/hello-module`, `easeo/shop-module` als path-deps
- `apps/easeo-website/site.config.json` — `"modules": ["easeo/hello-module"]` (shop niet actief)
- `apps/easeo-website/public/router.php` — vraagt `ModuleLoader` om extra routes
- `apps/easeo-website/.env.example` — add `PAYMENT_PROVIDER=`, `MOLLIE_API_KEY=`, `STRIPE_SECRET_KEY=`, `ADYEN_API_KEY=`
- `packages/cms-core/beheer/inc/nav.php` — dynamisch menu-item per geregistreerde module
- Root `composer.json` — add `packages/hello-module` + `packages/shop-module` test-scripts

---

## Tasks-outline

### Task D1: ModuleInterface + Registry (TDD)
- Schrijf interface + registry class
- Tests: `DummyModule` registreert route, beheer, hook, config → registry heeft juiste state
- Edge: dubbele route-registratie → exception met duidelijke melding

### Task D2: ModuleLoader leest site.config.json
- TDD: loader met `site.config.json` modules-lijst → vindt packages in vendor/, roept `register()` aan
- Module ontbreekt → duidelijke exception ("module X niet gevonden in vendor — composer install uitgevoerd?")
- Edge: module zonder `extra.easeo.module-class` in composer.json → exception

### Task D3: Hook-systeem
- TDD: `Hook::fire('cms.footer.before')` roept alle subscribers aan, buffer hun output
- Subscriber-return wordt geaccumuleerd tot één string
- Edge: throwing subscriber → log + continue met volgende subscribers (fail-soft)

### Task D4: RouteTable-merge
- TDD: core-routes (bestaand in `router.php`) + module-routes → gemergede tabel
- Module-routes kunnen core-routes niet overschrijven (duplicate pattern → exception)
- Edge: module-route match-order is registratie-volgorde (eerste module wint)

### Task D5: BeheerPages-registratie + dynamisch menu
- TDD: geregistreerde beheer-pages verschijnen in admin-nav
- Integratie: beheer-login + navigeer naar `/beheer/modules/hello` → werkt

### Task D6: hello-module package
- Schrijf composer.json + Module-class + HelloController + HelloPage
- Tests: module registreert `/hello` route; `Module::register()` bevat alle 4 extension points
- Integratie: in easeo-website `site.config.json` modules=["easeo/hello-module"] → `/hello` returneert 200 met "Hallo wereld"

### Task D7: shop-module composer.json + Module.php (leeg skelet)
- Schrijf composer.json, Module-class die alléén een beheer-menu-stub registreert
- Tests: kan geladen worden zonder errors

### Task D8: PaymentProvider interface + value-objects
- TDD: interface met createPayment / verifyWebhook / refund signatures
- PaymentStatus enum (PHP 8.1 backed enum)
- RefundResult readonly class
- NotImplementedException extends RuntimeException

### Task D9: PaymentProviderFactory
- TDD: `make(): PaymentProvider` leest `$_ENV['PAYMENT_PROVIDER']`, instantieert juiste adapter met API-key
- Onbekende provider → `InvalidArgumentException` met lijst geldige opties
- API-key ontbreekt → `RuntimeException` met instructie voor `.env`

### Task D10: 3 stub-adapters
- MollieProvider, StripeProvider, AdyenProvider — elk implementeert interface
- Alle methods `throw new NotImplementedException('Implement when eurolight kickoff starts — zie docs/eurolight-kickoff.md')`
- Tests: factory kan elk type instantiëren

### Task D11: App-integratie
- Update `apps/easeo-website/composer.json`: add hello-module als dependency
- Update `site.config.json`: modules lijst
- Update `router.php` of bootstrap: ModuleLoader::boot() vóór routing
- Update beheer-nav: dynamische items

### Task D12: Smoke-test eind-to-end
- `curl http://localhost:8000/hello` → 200 "Hallo wereld"
- Beheer: `/beheer/modules/hello` → pagina werkt
- Footer toont hook-output
- Module deactiveren in `site.config.json` → `/hello` → 404, beheer-menu-item weg

### Task D13: Documentatie voor module-auteurs
- Schrijf `packages/cms-core/docs/writing-a-module.md` — quickstart voor nieuwe modules
- Linkt naar hello-module als reference implementation

### Task D14: CHANGELOG + tag
- `packages/cms-core/CHANGELOG.md`: nieuwe feature — Module system
- `packages/shop-module/CHANGELOG.md`: initial skelet
- `packages/hello-module/CHANGELOG.md`: initial release
- Root CHANGELOG bijwerken
- Tags: `cms-core-v0.2.0`, `hello-module-v1.0.0`, `shop-module-v0.1.0`

---

## Open issues (te beslissen bij volledige uitwerking)
- Module-config-validatie: gebruikt `Registry::configSchema()` JSON-schema, een custom array-format, of Symfony/Config component?
- Hook-naming-conventie: `dot.notation.naming` vs `namespace/event` — keuze landt in docs
- Module-volgorde bij laden: alfabetisch, of `dependencies`-veld in composer.json van modules? (YAGNI vermoedelijk — alfabetisch is genoeg tot er conflicten zijn)
- Tests voor stub-adapters: gewoon `expectException`, of contract-tests die elke adapter dezelfde base-suite tegen mock-HTTP draait? (Stubs zijn stubs → `expectException` volstaat nu, contract-tests komen bij eurolight-echte-implementatie)

---
