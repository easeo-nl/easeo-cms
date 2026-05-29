# Changelog — easeo/cms-core

Alle wijzigingen aan `easeo/cms-core` staan in dit bestand.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

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
