# Changelog — easeo/cms-core

Alle wijzigingen aan `easeo/cms-core` staan in dit bestand.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

### Added — Plan 03 batch 6 (shop-module skeleton)
- **packages/shop-module/** — Shop-module skelet. Registreert alleen een geparkeerde beheer-tab. Payment-provider abstractie beschikbaar via `Easeo\Shop\Payment\PaymentProviderFactory::make()`.
- `Easeo\Shop\Payment\{PaymentProvider, PaymentStatus, RefundResult, NotImplementedException}` — public API.
- 3 stub adapters: `MollieProvider`, `StripeProvider`, `AdyenProvider` — alle methods werpen `NotImplementedException`. Echte implementaties komen bij eurolight-webshop kickoff.
- `PaymentProviderFactory` leest `PAYMENT_PROVIDER` env + bijbehorende API-key env. Throws RuntimeException als config ontbreekt.
- ~20 nieuwe tests via dataProvider-style stub-coverage.

### Added — Plan 03 batches 3+4+5 (router-integration + nav-merge + hello-module)
- `apps/easeo-website/public/module-router.php` — fallback entry that dispatches module-registered routes via `App::registry()->routes()->match()`. Boots App if not already booted; 404-safe on no match, 500-safe on handler exception.
- `apps/easeo-website/public/beheer-entry.php` — beheer-UI entry-point that calls `App::boot()` before including `cms-core/beheer/index.php`, ensuring modules are registered before the admin UI renders.
- Updated PHP dev-router (`router.php`) to consult the module RouteTable before falling through to the pagina-router catch-all; core routes always win.
- `packages/cms-core/beheer/index.php` now dispatches module-registered tabs: expands the allowed-tab check to include `App::registry()->beheerPages()` entries, enforces `admin_only` flag (403 for non-admins), invokes the renderer callable, and fails soft on renderer exceptions.
- `packages/cms-core/beheer/inc/layout-top.php` merges module beheer-pages into `$navItems` (appended after core items, separated by a divider), honouring the `admin_only` flag for nav visibility.
- `App::current()` — static accessor returning the most-recently-booted App instance (null before first boot). Lets cross-file includes like `beheer/index.php` access the Registry without re-booting.
- **packages/hello-module/** — first concrete cms-core module package. Registers `GET /hello`, a `hello` beheer-tab, and a `cms.footer.before` hook subscriber. Wired into `apps/easeo-website` via composer path-repo symlink.
- Smoke verified: `php -S localhost:8000 router.php` → `curl /hello` returns "Hallo wereld".

### Added — Plan 03 batch 2 (ModuleLoader + App integration)
- `Easeo\Cms\Module\ModuleLoader` — reads `site.config.json.modules`, resolves each package to a `composer.json` (klant-site vendor/ or monorepo packages/ path), reads `extra.easeo.module-class`, instantiates, and calls `register($registry)`.
- `Easeo\Cms\Module\{ModuleNotFoundException, InvalidModuleException}` for clear failure paths.
- `App::boot()` now creates a `Registry`, loads modules into it, and exposes it via `App::registry()`. Fail-soft: bad modules log via `error_log` and are skipped — boot continues.
- `App::loadedModules()` returns the list of successfully-loaded module names.

No router-integration with module routes yet (batch 3 wires them into entry-files), no beheer-nav merge yet (batch 4).

### Added — Plan 03 batch 1 (Module extension-point infrastructure)
- `Easeo\Cms\Module\ModuleInterface` — contract for cms-core module packages.
- `Easeo\Cms\Module\Registry` — aggregator that modules call into during `register()` to declare routes, beheer-pages, hooks, and config-schemas.
- `Easeo\Cms\Module\RouteTable` — internal route collection with duplicate-pattern detection and `match()` lookup.
- `Easeo\Cms\Module\BeheerPages` — admin-menu entry collection with kebab-case slug validation.
- `Easeo\Cms\Module\Hook` — named-hook dispatcher with priority-ordered subscribers and fail-soft error handling.
- `Easeo\Cms\Module\{DuplicateRouteException, DuplicateBeheerPageException}` for clear failure paths.

No loader, router-integration, or beheer-nav-merge yet — those come in later batches with the first real consumer (hello-module).

## [0.2.0-rc2] - 2026-06-01

Brings Plan 02 (12-factor config-split) and Plan 07 batch 1 (lazy migrations runtime) to a tagged release. Klant-sites kunnen vanaf nu `composer require easeo/cms-core: ^0.2.0-rc2` om `Environment`, `SiteConfig`, `SecretStatus`, `Migration\Runner`, `Bootstrap\Bootstrapper`, en de Mailer-SMTP-from-env logica te krijgen. Volledig backwards-compatible voor sites die nog op site.json[smtp] draaien — Mailer valt automatisch terug.

### Added — Plan 02 batch 3 (migration tooling + beheer)
- `Easeo\Cms\Config\SiteJsonMigrator` — splits legacy `data/site.json` into `data/site.config.json` (branding) + `.env` template (secrets, commented out for operator review) + timestamped backup. Idempotent.
- `tools/migrate-site-json-to-split.php` CLI — dry-run by default, `--apply` to commit. Takes `--app=path` to target a klant-site app-root.
- `beheer/pages/geheimen.php` — admin-only read-only status overview of environment-backed secrets via `SecretStatus::summary()`. Never shows values, only configured/not-configured. Includes copy-paste `.env` example.
- Sidebar nav item "Geheimen" (admin-only), tab whitelisted in `beheer/index.php`.
- Dutch + English lang strings for the secrets-management UI.

### Changed — Plan 02 batch 2 (Mailer SMTP from env)
- `Mailer::send()` now reads SMTP config from environment variables when `SMTP_HOST` is set (12-factor). Falls back to legacy `site.json["smtp"]` (with crypto-decrypted password) when env is unset, so existing klant-sites continue to send mail until they run the (later) site.json → .env migration script.
- New env vars consumed: `SMTP_HOST`, `SMTP_PORT` (default 465), `SMTP_USERNAME`, `SMTP_PASSWORD` (plaintext), `SMTP_FROM_EMAIL` (defaults to username), `SMTP_FROM_NAME` (defaults to company name from site.json or `"Website"`), `SMTP_ENCRYPTION` (default `ssl`).
- `Mailer::sendSmtp()` now takes a pre-resolved, normalized config array — password is always plaintext at this point (env path: never encrypted; site.json path: decrypted by `resolveSmtpConfig`). No in-method decryption.
- Internal `resolveSmtpConfig()` returns a normalized config array with a `source` field (`'env'` or `'site.json'`) indicating which config path was used.
- `Mailer::encryptSmtpPassword()` and `Mailer::decryptSmtpPassword()` are unchanged — still needed for the BC fallback path and the (later) site.json → .env migration script.

### Added — Plan 02 batch 1 (12-factor config-split foundation)
- `Easeo\Cms\Config\Environment` — vlucas/phpdotenv wrapper with type-cast accessors (`get`/`has`/`bool`/`int`/`require`).
- `Easeo\Cms\Config\SiteConfig` — JSON wrapper for `data/site.config.json` with dot-notation get/set/save. Falls back to `site.template.json` then legacy `site.json` so existing klant-sites continue to render during the transition.
- `Easeo\Cms\Config\SecretStatus` — beheer-UI helper that reports which environment-backed secrets are configured (status-only, no values).
- `App::boot()` now loads `.env` (and `.env.local` override) from `$appRoot/` before `runPendingMigrations` so migrations see env vars.
- `apps/easeo-website/.env.example` and `apps/_skeleton/.env.example` templates.

### Changed — Plan 02 batch 1
- `ContentRepository::siteValue()` delegates to `SiteConfig::get()` — same API, same dot-notation, same default-on-missing semantics. All existing callers continue to work.

### Added — Plan 07 batch 1
- `Easeo\Cms\Migration\Runner` runs forward-only migrations at first request after upgrade. Reads `data/.schema-version`, scans `packages/cms-core/migrations/`, applies pending migrations with `flock` concurrency-guard.
- `Easeo\Cms\Migration\MigrationInterface` contract for individual migrations.
- `Easeo\Cms\Migration\SchemaVersion` read/write helper for `data/.schema-version` (atomic tmp+rename writes).
- `Easeo\Cms\Bootstrap\Bootstrapper` initializes `data/` on first deploy from `site.template.json`. Idempotent.
- `bin/easeo-bootstrap` CLI wrapper for first-deploy data initialization.
- Initial migration `0001_initial_skeleton.php` as safety-net baseline.
- `App::boot()` now runs pending migrations automatically (no-op if `EASEO_DATA` is unwritable, e.g. in CLI/tests where bootstrap has not run).

## [0.2.0-rc1] - 2026-05-29

### Added
- `Easeo\Cms\App::boot($appRoot)->run()` entry-aggregator. Klant-sites kunnen vanaf één regel booten; `boot()` wraps `Constants::bootstrap` + secure session defaults (httponly + samesite=Strict + strict_mode + cookie_secure indien HTTPS) + `session_start()`. `run()` is een no-op placeholder voor toekomstige front-controller routing.
- 4 PHPUnit tests voor `App` (boot retourtype, trailing-slash strip, constants definitie, run no-op).

### Changed — Plan 01 Fase B complete (PSR-4 refactor)
- **BREAKING** Procedurele `includes/` engines hernoemd naar PSR-4 classes onder namespace `Easeo\Cms\`:
  - `content.php` → `Easeo\Cms\Content\ContentRepository`
  - `lang.php` → `Easeo\Cms\Lang\Translator`
  - `brand.php` → `Easeo\Cms\Branding\BrandConfig`
  - `mailer.php` → `Easeo\Cms\Mail\Mailer` (crypto-key BC behouden)
  - `form-engine.php` → `Easeo\Cms\Form\FormEngine`
  - `blog-engine.php` → `Easeo\Cms\Blog\BlogEngine`
  - `audit.php` → `Easeo\Cms\Audit\AuditLogger`
  - `legal.php` → `Easeo\Cms\Legal\LegalPages`
  - `media-engine.php` → `Easeo\Cms\Media\MediaLibrary`
  - `navigation.php` → `Easeo\Cms\Navigation\Menu`
  - `structured-data.php` → `Easeo\Cms\Seo\StructuredData`
  - `rate-limiter.php` → `Easeo\Cms\Security\RateLimiter`
- Template-files (header, footer, cookie-consent, tracking-head, tracking-body) verplaatst naar `packages/cms-core/templates/layout/`
- Legacy-bridge verwijderd; constants verplaatst naar `Easeo\Cms\Constants` (call `Constants::bootstrap($appRoot)` vanuit entry-files)
- `packages/cms-core/src/legacy/` directory bestaat niet meer; alleen `vendor-legacy/phpmailer/` blijft tijdelijk over tot een latere `composer require phpmailer/phpmailer`

### Added
- PHPUnit testsuites voor alle gemigreerde engines (130+ tests)
- AST-based `tools/rename-engine.php` voor herhaalbare function→method conversies
- `Easeo\Cms\Constants::bootstrap()` als enige bron van EASEO_APP/DATA/CORE/TEMPLATES/LANG/BEHEER/ROOT constants

### Migration impact
- Klant-sites die `easeo/cms-core` als dependency installeren moeten in elk entry-file `\Easeo\Cms\Constants::bootstrap(dirname(__DIR__))` aanroepen na `vendor/autoload.php` (skeleton-template B1 doet dit reeds correct)
- Crypto-key voor SMTP-password (Mailer) is byte-identisch aan legacy om bestaande encrypted passwords decryptbaar te houden

## [0.1.0-rc2] - 2026-05-28

### Fixed
- `release.yml`: `contents: write` permission op release-notes job (rc1 faalde met 403 op GitHub release creation, zie PR #2)
- `release.yml`: vervang dode `splitsh-lite` v2.0.0 download door ingebouwde `git subtree split`
- Branch-protection actief op `main` met required CI checks

### Added
- CI badge in monorepo README
- `apps/_skeleton/` thin site-app template (Plan 06 B1 + B3)
- `docs/packagist-setup.md` en `docs/branch-protection.md` runbooks

## [0.1.0-rc1] - 2026-05-23

### Added
- Eerste release-candidate van cms-core na monorepo-split (Plans 01-04)
- CI-pipeline met PHPUnit matrix (PHP 8.1/8.2/8.3) + integration smoke op fixture-app
- Packagist-publicatie via splitsh/lite mirror-repo

### Schema impact
- N.v.t. — initial release

### Action voor site-beheerders
- N.v.t. — geen klant-installaties yet (eerste productie-cutover komt in Plan 08)
