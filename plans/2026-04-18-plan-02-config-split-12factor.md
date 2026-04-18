# Plan 02 — Config-split volgens 12-factor (Fase C)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 01 afgerond en op main gemerged is (gedrag van caller-code beïnvloedt test-scenarios).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scheidt configuratie in publieke branding (`site.config.json`, bewerkbaar via beheer-UI) en geheimen (`.env`, deploy-only). Alle API keys, SMTP-wachtwoord, database-credentials verhuizen naar `.env`. Beheer-UI toont alleen of een geheim "geconfigureerd" is, niet de waarde.

**Architectuur:** `vlucas/phpdotenv` als dependency; `site.config.json` blijft JSON voor UI-editability. Bestaande `site.json` wordt gesplitst tijdens migratie-script dat bij Plan 02-deploy eenmalig draait. Beheer-UI krijgt een "Geheimen"-tabblad dat status-only informatie toont en instructies voor deployment-update geeft.

**Tech Stack:** `vlucas/phpdotenv ^5.6`, bestaande PSR-4 classes uit Plan 01.

**Afhankelijkheden:** Plan 01 moet afgerond zijn (alle engines namespaced, `Constants::bootstrap()` werkt).

---

## Bestandsstructuur

**Aangemaakt:**
- `apps/easeo-website/.env.example` — template met alle keys en lege values
- `apps/easeo-website/.env` — runtime (GITIGNORED, wordt door `install.php` aangemaakt)
- `apps/easeo-website/site.config.json` — runtime (GITIGNORED, wordt door `install.php` aangemaakt vanuit template)
- `packages/cms-core/src/Config/Environment.php` — wrapper rondom phpdotenv
- `packages/cms-core/src/Config/SiteConfig.php` — wrapper rondom JSON-config (vervangt `site()` helper uit ContentRepository)
- `packages/cms-core/src/Config/SecretStatus.php` — read-only status-checker ("is Mollie-key ingesteld?")
- `packages/cms-core/tests/Config/EnvironmentTest.php`
- `packages/cms-core/tests/Config/SiteConfigTest.php`
- `packages/cms-core/tests/Config/SecretStatusTest.php`
- `packages/cms-core/beheer/pages/Geheimen.php` — nieuwe beheer-pagina voor status-overzicht
- `tools/migrate-site-json-to-split.php` — eenmalig migratie-script

**Gewijzigd:**
- `apps/easeo-website/composer.json` — add `vlucas/phpdotenv`
- `apps/easeo-website/public/index.php` (en andere entries) — laadt `.env` bij bootstrap
- `packages/cms-core/src/Content/ContentRepository.php` — `siteValue()` delegeert naar `SiteConfig`
- `packages/cms-core/src/Mail/Mailer.php` — leest SMTP uit `.env`, niet uit `site.json`
- `packages/cms-core/beheer/inc/nav.php` — add "Geheimen" menu-item
- `.gitignore` — add `apps/*/site.config.json`, `apps/*/.env`
- `apps/easeo-website/install.php` — schrijft `.env` + `site.config.json` bij eerste setup

---

## Tasks-outline

### Task C1: Dependency + Environment-class
- Composer require `vlucas/phpdotenv`
- Schrijf `Environment` class met TDD (get/has/require methods + type-casting)
- Unit tests

### Task C2: SiteConfig-class
- TDD: schrijf `SiteConfig::get('brand.color_primary')` — leest uit `apps/easeo-website/site.config.json`
- Ondersteunt dot-notation (gelijk aan oude `site()` helper)
- Heeft `set()` + `save()` voor beheer-UI

### Task C3: ContentRepository delegeert naar SiteConfig
- Refactor `ContentRepository::siteValue()` → delegatie naar `SiteConfig::get()`
- Backwards-compat: alle bestaande callers blijven werken

### Task C4: SecretStatus-checker
- TDD: `SecretStatus::isConfigured('MOLLIE_API_KEY')` retourneert bool
- Nooit de echte waarde teruggeven
- Lijst van bekende secret-keys hard-coded (whitelisting voor UI)

### Task C5: Mailer leest SMTP uit .env
- Verplaats SMTP-config uit `site.json.smtp.*` naar `.env` (SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_FROM_EMAIL, SMTP_FROM_NAME)
- Verwijder de `encrypt_smtp_password()` / `decrypt_smtp_password()` logic — niet meer nodig, `.env` is sowieso buiten DocumentRoot en nooit in git
- Update `Mailer::send()` + beheer SMTP-configuratie-pagina

### Task C6: Migratie-script
- `tools/migrate-site-json-to-split.php` leest bestaand `apps/easeo-website/data/site.json`, splitst in `site.config.json` + `.env`
- Oude `site.json.smtp` wordt geconverteerd (password wordt gedecrypt en klaar gezet voor `.env`)
- Backup-copy van origineel wordt achtergelaten als `site.json.pre-split-backup-<timestamp>`
- Script is idempotent: als `site.config.json` al bestaat, doet niks

### Task C7: Beheer "Geheimen"-pagina
- Nieuwe pagina in `packages/cms-core/beheer/pages/Geheimen.php`
- Toont lijst: `SMTP_HOST: ✓ geconfigureerd`, `MOLLIE_API_KEY: ✗ niet geconfigureerd (deploy-only)`
- Toont instructie: "Geheimen worden bij deployment in `.env` geplaatst. Contact systeembeheerder voor wijzigingen."
- Formuliertekst bewerkt dus géén `.env` direct (te gevaarlijk voor file-permissions + race conditions met deploy)

### Task C8: Install-wizard schrijft beide bestanden
- Update `apps/easeo-website/public/install.php`: bij eerste setup vraagt user branding-velden → schrijft `site.config.json`. Voor geheimen toont het instructies voor CLI-update van `.env`
- `.env.example` wordt automatisch gekopieerd naar `.env` met lege values

### Task C9: Bootstrap laadt `.env`
- Update `apps/easeo-website/public/index.php` + andere entries:
```php
require __DIR__ . '/../vendor/autoload.php';
\Easeo\Cms\Constants::bootstrap(dirname(__DIR__));
\Easeo\Cms\Config\Environment::load(EASEO_APP . '/.env');
```
- Als `.env` ontbreekt: fallback naar `.env.example` met waarschuwing in error-log, beheer-UI toont deployment-banner

### Task C10: Integratie + regressie
- Run migratie-script op staging-kopie van productie-`site.json`
- Run PageRenderingTest — alles 200 OK
- Stuur test-email via contact-form met staging-SMTP — ontvangen?
- Commit + CHANGELOG + plan-02-complete tag

---

## Open issues (te beslissen bij volledige uitwerking)
- `.env`-deployment-strategie: SCP vanaf dev-machine, of Ansible/Deploy-tool?
- Encryptie van `.env` at-rest op VPS? (Hostinger VPS heeft geen disk-encryptie out-of-the-box)
- Moet beheer-UI wél via SSH/tunnel `.env` kunnen bewerken? (Standpunt: nee, te risicovol. SSH + editor is de aangewezen flow.)

---
