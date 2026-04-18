---
title: easeo-cms monorepo split — core + modules + site-apps
date: 2026-04-17
status: approved
author: Nick Aldewereld
---

# easeo-cms monorepo split — core + modules + site-apps

## Context & doel

`easeo-cms` is nu één PHP-app: routing, `includes/` engines, `templates/` blokken, en `beheer/` admin in één repo, met `site.template.json` voor per-site branding. Elke nieuwe site (easeo.nl vandaag, eurolight-webshop later) zou via fork-en-divergeren ontstaan — onderhoud daarvan schaalt niet.

Doel: easeo-cms omvormen naar een **monorepo met Composer packages**, zodat:

- Meerdere sites (nu: easeo.nl; later: eurolight-webshop en verder) dezelfde core delen
- Updates aan core/modules centraal gemaakt worden en via `composer update` op alle sites landen
- Modules (bv. shop) bidirectioneel stromen: fix in context A, landt op alle sites via Packagist-tag
- Geheimen (API keys) gescheiden blijven van branding-config

**Scope**: eurolight-webshop is expliciet geparkeerd. We bouwen alléén de infra + leeg shop-module-skelet met payment-abstractie. Echte shop-logica wacht op eurolight-kickoff.

## Architectuur

**Monorepo met drie packages en één site-app**:

| Package / App | Rol | Composer-naam | Namespace |
|---|---|---|---|
| `packages/cms-core` | Kern-library: engines, templates, beheer-framework, router, module-registry | `easeo/cms-core` | `Easeo\Cms\` |
| `packages/shop-module` | Leeg skelet + payment-interface + stub-adapters (Mollie/Stripe/Adyen) | `easeo/shop-module` | `Easeo\Shop\` |
| `packages/hello-module` | Smoke-test module — bewijst registratie-systeem werkt | `easeo/hello-module` | `Easeo\Hello\` |
| `apps/easeo-website` | Thin site-app voor easeo.nl | n.v.t. | n.v.t. |

**Toekomstige eurolight-webshop** leeft in een eigen repo (buiten deze monorepo) en doet `composer require easeo/cms-core easeo/shop-module` via Packagist.

## Monorepo-layout

```
easeo-cms/                       (bestaande GitHub-repo, wordt monorepo)
├── composer.json                workspace-achtige root
├── CHANGELOG.md                 top-level release-overzicht
├── specs/                       design-specs (deze file)
├── docs/                        bestaande Jekyll GitHub-Pages site (ongewijzigd)
├── packages/
│   ├── cms-core/
│   │   ├── composer.json        name: easeo/cms-core, PSR-4: Easeo\Cms\
│   │   ├── CHANGELOG.md
│   │   ├── src/                 voormalige includes/, per-engine namespace
│   │   │   ├── Module/
│   │   │   │   ├── ModuleInterface.php
│   │   │   │   └── Registry.php
│   │   │   ├── Form/Mailer.php
│   │   │   ├── Form/RateLimiter.php
│   │   │   ├── Branding/BrandConfig.php
│   │   │   ├── Lang/Translator.php
│   │   │   ├── Blog/BlogEngine.php
│   │   │   └── ...
│   │   ├── templates/           herbruikbare content-blokken
│   │   ├── beheer/              admin framework + base pages
│   │   ├── lang/                i18n-strings
│   │   └── tests/               PHPUnit
│   ├── shop-module/
│   │   ├── composer.json        name: easeo/shop-module
│   │   ├── src/
│   │   │   ├── Module.php       registratie
│   │   │   └── Payment/
│   │   │       ├── PaymentProvider.php    interface
│   │   │       ├── PaymentStatus.php      value-object
│   │   │       ├── PaymentProviderFactory.php
│   │   │       ├── MollieProvider.php     stub
│   │   │       ├── StripeProvider.php     stub
│   │   │       └── AdyenProvider.php      stub
│   │   └── tests/
│   └── hello-module/
│       ├── composer.json
│       ├── src/Module.php
│       └── tests/
└── apps/
    └── easeo-website/
        ├── composer.json        path-repo: ../../packages/*
        ├── .env.example         template, zonder echte waarden
        ├── .env                 GITIGNORED — API keys, SMTP, DB
        ├── site.config.json     branding, modules-lijst, content-settings
        ├── site.template.json   structuur-referentie (in git)
        ├── public/              DocumentRoot
        │   ├── index.php
        │   ├── .htaccess
        │   └── assets/
        ├── content/             blog-posts, pagina's, media-uploads
        └── templates/           site-specifieke overrides
```

### Composer path-repositories

In `apps/easeo-website/composer.json`:
```json
{
  "require": {
    "easeo/cms-core": "*",
    "easeo/hello-module": "*"
  },
  "repositories": [
    {"type": "path", "url": "../../packages/*", "options": {"symlink": true}}
  ]
}
```
`symlink: true` → wijzigingen in `packages/cms-core/src/` zijn direct zichtbaar in site-app zonder `composer update`.

### Productie-deploy op Hostinger-VPS
1. Clone monorepo naar `/var/www/easeo-cms`
2. `cd apps/easeo-website && composer install --no-dev --optimize-autoloader`
3. DocumentRoot Apache/Nginx → `/var/www/easeo-cms/apps/easeo-website/public/`
4. `.env` bevat productie-waarden (niet in git — geüpload via separate deploy-stap)

## Module-systeem

### Interface & registry

`Easeo\Cms\Module\ModuleInterface`:
```php
interface ModuleInterface {
    public static function register(Registry $registry): void;
}
```

`Easeo\Cms\Module\Registry` biedt vier extension points:
- `routes(array $patterns)` — regex → callable routing, wordt gemerged met core-routes
- `beheerPages(array $pages)` — slug → PageClass, verschijnt in admin-menu
- `hooks(array $hooks)` — naam → callable (bv. `cms.footer.before`, `cms.head.meta`)
- `configSchema(string $key, array $schema)` — definieert welke config-velden module verwacht

### Activatie per site

`apps/easeo-website/site.config.json`:
```json
{
  "modules": ["easeo/hello-module"],
  "shop": {"provider": "mollie", "test_mode": true}
}
```

### Bootstrap-flow

`packages/cms-core/src/bootstrap.php`:
1. Laad `.env` via `vlucas/phpdotenv`
2. Laad `site.config.json`
3. Voor elke module in `config.modules[]`:
   - Vind `vendor/{module}/composer.json`
   - Instantieer klasse uit `extra.easeo.module-class` veld
   - Roep `Module::register($registry)` aan
4. Router handelt request af met gemergede routes

### Module's composer.json bevat registratie-metadata

```json
{
  "name": "easeo/shop-module",
  "extra": {
    "easeo": {"module-class": "Easeo\\Shop\\Module"}
  }
}
```

## Payment-abstractie

Shop-module skelet levert alleen de abstractie, geen cart/checkout-logica.

```php
namespace Easeo\Shop\Payment;

interface PaymentProvider {
    public function createPayment(int $amountCents, string $currency, string $orderId, string $returnUrl, string $webhookUrl): string;
    public function verifyWebhook(array $payload, array $headers): PaymentStatus;
    public function refund(string $paymentId, int $amountCents): RefundResult;
}
```

Drie stub-adapters (`MollieProvider`, `StripeProvider`, `AdyenProvider`) implementeren de interface met `throw new NotImplementedException()` in elke methode — zodat typesystem klopt, maar daadwerkelijke calls pas gebouwd worden bij eurolight-kickoff.

`PaymentProviderFactory::make()`:
```php
$provider = $_ENV['PAYMENT_PROVIDER'] ?? 'mollie';
return match ($provider) {
    'mollie' => new MollieProvider($_ENV['MOLLIE_API_KEY']),
    'stripe' => new StripeProvider($_ENV['STRIPE_SECRET_KEY']),
    'adyen'  => new AdyenProvider($_ENV['ADYEN_API_KEY']),
    default  => throw new InvalidArgumentException("Unknown provider: $provider"),
};
```

Switchen van provider = 1 env-var wijzigen.

## Config-split (12-factor)

**`site.config.json`** — runtime bestand, **gitignored**. `site.template.json` (structuur-referentie met lege values) is wél in git en wordt bij `install.php` gekopieerd naar `site.config.json`, waarna beheer-UI de waarden vult:
- `brand.*` (kleuren, fonts, logo, favicon)
- `company.*` (naam, KvK, adres — marketing-data)
- `social.*`
- `tracking.*` (GTM ID, GA ID — publieke identifiers, geen secrets)
- `modules[]`
- `shop.provider`, `shop.test_mode`

**`.env`** (gitignored, per-environment):
- `DB_HOST`, `DB_USER`, `DB_PASS`
- `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`
- `PAYMENT_PROVIDER`
- `MOLLIE_API_KEY`, `STRIPE_SECRET_KEY`, `ADYEN_API_KEY`
- `APP_ENV` (production/staging/local)

**`.env.example`** in git als template — kopiëren naar `.env` en invullen bij setup.

**Beheer-UI** bewerkt alléén `site.config.json`. `.env` is deploy-only en niet bereikbaar via web.

## Migratiepad (A → E)

### Fase A — Monorepo-skelet naast bestaande code (niet-destructief)
- `git mv` root-bestanden naar `apps/easeo-website/public/` (behalve `includes/`, `templates/`, `beheer/`, `lang/`)
- `git mv includes/ → packages/cms-core/src/`
- `git mv templates/ → packages/cms-core/templates/`
- `git mv beheer/ → packages/cms-core/beheer/`
- `git mv lang/ → packages/cms-core/lang/`
- Schrijf root- + per-package `composer.json`
- Smoke-test: huidige site blijft werken via oude require-paths (tijdelijk een `legacy-bridge.php` die de paden mapt)

### Fase B — Namespace-refactor via automated rename-script
- **Rename-script**: PHP-script dat `tokens_get_all()` gebruikt om alle functies per bestand te scannen, statische class-methods te genereren (`mailer.php::send_form_email()` → `Easeo\Cms\Form\Mailer::send()`), en `use`-statements toe te voegen aan aanroeper-bestanden
- **`--dry-run` modus** draait eerst, print alle voorgestelde wijzigingen
- Script werkt file-by-file met commit per engine (brand → lang → form-engine → ...) voor reviewability
- Na elke file: `composer dump-autoload && php -l` + `php test-harness.php` om syntax + loadbaarheid te verifiëren
- `require_once`-statements vervangen door Composer autoload
- `legacy-bridge.php` kan weg na laatste engine

### Fase C — Config-split
- Installeer `vlucas/phpdotenv` in cms-core
- Split bestaande `site.json`: niet-gevoelig → `apps/easeo-website/site.config.json`; geheimen → `.env`
- `.env.example` maken (alle keys, lege values)
- Pas `install.php` aan: schrijft `.env` bij eerste setup, UI toont duidelijk welke velden pas bij deploy invullen
- Beheer-UI: alleen `site.config.json`-velden bewerkbaar. Endpoint om `.env`-status te tonen ("Mollie geconfigureerd? ja/nee") zonder waarden te lekken

### Fase D — Module-infra + hello-module + shop-skelet
- `packages/cms-core/src/Module/{ModuleInterface,Registry}.php`
- `packages/cms-core/src/bootstrap.php` leest modules uit `site.config.json`
- `packages/hello-module/` — registreert `/hello` route + beheer-pagina
- `packages/shop-module/` — leeg skelet + payment-interface + 3 stub-adapters + `Module.php` met `register()` die beheer-stubs toevoegt
- Smoke-test: `site.config.json.modules = ["easeo/hello-module"]` → `/hello` werkt, beheer-pagina zichtbaar

### Fase E — Deploy & verificatie
- Deploy naar staging-VPS
- Regressietest alle easeo.nl-features: blog listing, blog-post, contact-form (met submit), cookie-consent, beheer-login, pagina-router (bestaande pagina's in `content/`), sitemap, feed
- Lighthouse / PageSpeed vóór-en-na vergelijken
- Pas daarna productie-cutover

## Release-workflow & versioning

**Git tags per package**:
```
cms-core-v1.0.0
shop-module-v0.1.0
hello-module-v1.0.0
```

**CHANGELOG per package** (`packages/*/CHANGELOG.md`), plus top-level `CHANGELOG.md` voor monorepo-events.

**Packagist-publicatie** — per package:
- Aanmaken op Packagist met source-URL `github.com/easeo-nl/easeo-cms` + subdir `packages/cms-core`
- GitHub webhook → Packagist pikt nieuwe tags automatisch op

**Semver-discipline**:
- PATCH (1.0.1): bugfix, geen API-wijziging
- MINOR (1.1.0): nieuwe features, backwards-compatible
- MAJOR (2.0.0): breaking change in `ModuleInterface`, `Registry`, of public classes in `src/`

**Privé-alternatief** als Packagist niet wenselijk: consumers doen `composer.json` `{"repositories": [{"type": "vcs", "url": "git@github.com:easeo-nl/easeo-cms"}]}` — vereist SSH-auth, geen publicatie.

## Toekomstige eurolight-integratie

Wanneer eurolight-kickoff start:

1. Nieuw repo `eurolight-webshop` buiten deze monorepo
2. `composer.json`:
   ```json
   {
     "require": {
       "easeo/cms-core": "^1.0",
       "easeo/shop-module": "^0.1"
     }
   }
   ```
3. `site.config.json` met `"modules": ["easeo/shop-module"]` + shop-config
4. `.env` met eurolight-specifieke Mollie-keys
5. `composer install` — klaar

**Shop-logica wordt dan pas gebouwd** in `packages/shop-module/` van deze monorepo, PR → tag → Packagist → eurolight `composer update`.

**Bidirectionele flow** — bug ontdekt in eurolight-productie:
1. Reproduceer in monorepo
2. Fix + test in `packages/shop-module/`
3. Tag `shop-module-v0.1.1`
4. `cd eurolight-webshop && composer update easeo/shop-module`

## Testen

- Elk package heeft `packages/*/tests/` met PHPUnit
- Root `composer test` draait alle suites via `phpunit --configuration` per package
- Hello-module's route + beheer-pagina fungeert als integratie-smoketest van module-systeem
- Payment-adapters hebben contract-tests: elke adapter doet dezelfde test-suite tegen mock-endpoints

## Risico's & mitigatie

| Risico | Mitigatie |
|---|---|
| Namespace-refactor breekt production easeo.nl | Fase B file-by-file met `legacy-bridge.php`, commit per engine, elke commit testen; staging-deploy vóór productie |
| Composer path-repo `symlink: true` werkt niet op Windows dev-machines | N.v.t. — Linux-only developer-setup |
| Packagist-vertraging bij tag-push | Acceptabel (1-2 min); of manual update trigger via Packagist-API |
| Module-config-conflict tussen twee modules | `configSchema()` valideert namespace per module (bv. `shop.*`, `hello.*`) |
| Stub-adapters in shop-module leveren verwarring ("werkt niet!") | Duidelijke `@throws NotImplementedException` docblocks + runtime-error met link naar eurolight-kickoff-doc |
| Jekyll `docs/` pikt `packages/` of `specs/` op | Jekyll serveert alleen vanuit `docs/` directory — packages en specs leven buiten, dus geen conflict |

## Open issues (nog te beslissen in implementation plan)

- Precieze PHPUnit-config (bootstrap-file, code coverage target)
- Automated changelog (release-please conventional-commits) ja/nee — gedeclineerd in brainstorm; kan later
- Beheer-UI-ontwerp voor `.env`-status-indicator (alleen boolean per integratie, geen waarden)
- Wanneer `hello-module` verwijderen? Behouden als permanente smoke-test, of alleen tijdens Fase D?

## Volgende stap

Dit design wordt input voor een implementation-plan dat Fase A → E als concreet werkpakketten uitspelt, per fase met testcriteria en rollback-stappen.
