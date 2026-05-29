# Changelog — easeo/cms-core

Alle wijzigingen aan `easeo/cms-core` staan in dit bestand.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

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
