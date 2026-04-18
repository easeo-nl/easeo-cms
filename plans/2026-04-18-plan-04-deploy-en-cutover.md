# Plan 04 — Deploy + productie-cutover (Fase E)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plans 01+02+03 afgerond zijn (deployment-details afhangen van daadwerkelijke configuratie op VPS).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deploy de nieuwe monorepo-structuur naar staging, regressietest alle features, en verhuis productie-easeo.nl van de oude structuur naar de nieuwe. Publiceer `easeo/cms-core` + `easeo/shop-module` + `easeo/hello-module` op Packagist zodat toekomstige externe site-apps (eurolight) ze kunnen requiren.

**Architectuur:** Staging = tweede VPS (of subdomein op bestaande VPS) die complete monorepo uitcheckt. Productie-cutover via DNS-switch óf via Apache/Nginx DocumentRoot-wijziging (sneller, rollback trivialer). Packagist-publicatie via subpath-directories in monorepo.

**Tech Stack:** Hostinger managed VPS, Apache/Nginx, PHP 8.1+ FPM, Composer 2.x, git, Packagist.

**Afhankelijkheden:** Plans 01 + 02 + 03 allemaal compleet, main branch stabiel, alle tests pass.

---

## Bestandsstructuur

**Aangemaakt:**
- `.github/workflows/ci.yml` — CI draait tests + phpstan (optioneel) bij elke push/PR
- `deploy/staging.sh` — shell-script voor staging-deploy (pull + composer install + restart fpm)
- `deploy/production-cutover.md` — runbook voor productie-switch
- `deploy/rollback.md` — runbook voor terugdraaien
- `apps/easeo-website/public/healthcheck.php` — simpele endpoint die alle critical integraties checkt
- `docs/packagist-setup.md` — instructies voor aanmaken packagist-accounts + webhooks

**Gewijzigd:**
- Apache/Nginx site-config op VPS: DocumentRoot → `apps/easeo-website/public/`
- DNS: n.v.t. (same domain, new docroot)
- `.env` op VPS: productie-waarden ingevuld (Mollie live-key, SMTP-wachtwoord)

---

## Tasks-outline

### Task E1: CI-pipeline (GitHub Actions)
- Workflow bij push/PR: `composer install`, `composer test` (alle packages), optioneel phpstan
- Matrix: PHP 8.1, 8.2, 8.3
- Badge in README
- **Niet-blokkerende tasks** voor cutover als CI groen is

### Task E2: Staging-VPS provisioneren
- Subdomein of aparte VPS (afhankelijk van Hostinger-setup user-side)
- SSH-key + git-clone toegang
- PHP-FPM 8.1+, Apache/Nginx, SSL via Let's Encrypt
- Composer global install
- Verificatie: `composer --version`, `php -v`, `git --version`

### Task E3: Eerste staging-deploy
- `git clone` monorepo op staging
- `cd apps/easeo-website && composer install --no-dev --optimize-autoloader`
- Apache/Nginx site-config: DocumentRoot → staging-pad
- `.env` met staging-waarden (test-SMTP, Mollie test-key)
- `site.config.json` gekopieerd van productie (of opnieuw ingevuld met staging-branding)
- Data-kopie: `apps/easeo-website/data/` gesynced vanuit productie (rsync)
- Verificatie: staging-URL rendert homepage + blog + contact

### Task E4: Staging regressie-test
- Check-list alle pagina's: homepage, blog listing, blog-posts (per categorie + per post), contact, privacy, voorwaarden, cookiebeleid, sitemap, feed
- Check-list alle beheer-acties: login, site-config bewerken, blog-post schrijven, form bewerken, geheimen-pagina
- Check-list integraties: contact-form stuurt test-mail, cookie-consent werkt, tracking-pixels laden, Google Fonts laden
- Lighthouse/PageSpeed vergelijken met productie-baseline
- **Stoplicht:** groen = door, geel = los op vóór cutover, rood = plan-fail, terug naar voorgaande plan

### Task E5: Healthcheck-endpoint
- `apps/easeo-website/public/healthcheck.php`:
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
\Easeo\Cms\Constants::bootstrap(dirname(__DIR__));
\Easeo\Cms\Config\Environment::load(EASEO_APP . '/.env');

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'cms_core_version' => 'x.y.z',
    'modules_loaded' => \Easeo\Cms\Module\ModuleLoader::loadedModules(),
    'site_config_ok' => \Easeo\Cms\Config\SiteConfig::isValid(),
    'env_loaded' => \Easeo\Cms\Config\Environment::isLoaded(),
    'smtp_configured' => \Easeo\Cms\Config\SecretStatus::isConfigured('SMTP_HOST'),
    'timestamp' => gmdate('c'),
]);
```
- Niet publiek linken — alleen voor monitoring-tools en debugging

### Task E6: Productie-cutover-runbook
- `deploy/production-cutover.md`:
  1. Maak DB-backup + file-backup (tar van huidige easeo-nl-root)
  2. `git clone` monorepo in `/var/www/easeo-nl-new/`
  3. Sync `.env` + `site.config.json` + `data/` naar `/var/www/easeo-nl-new/apps/easeo-website/`
  4. `cd .../apps/easeo-website && composer install --no-dev -o`
  5. Test: `curl http://localhost/healthcheck.php` via lokaal Apache alias → alle green
  6. Switch Apache DocumentRoot: `/var/www/easeo-nl` → `/var/www/easeo-nl-new/apps/easeo-website/public`
  7. `sudo systemctl reload apache2` (of nginx)
  8. Externe check: `curl -sI https://easeo.nl` → 200 + Lighthouse score vergelijken
  9. Als iets faalt: rollback-stap uitvoeren (zie `rollback.md`)

### Task E7: Rollback-runbook
- `deploy/rollback.md`:
  1. Switch Apache DocumentRoot terug naar `/var/www/easeo-nl` (oude structuur)
  2. `sudo systemctl reload apache2`
  3. Verifieer site werkt via oude structuur
  4. Maak issue met root-cause + stuur logs naar team
  - Oude structuur blijft 30 dagen beschikbaar, daarna archiveren

### Task E8: Packagist-publicatie
- Accounts: easeo-nl organization op Packagist
- Submit elk package:
  - `easeo/cms-core` — source: `github.com/easeo-nl/easeo-cms`, subpath: `packages/cms-core`
  - `easeo/shop-module` — subpath: `packages/shop-module`
  - `easeo/hello-module` — subpath: `packages/hello-module`
- GitHub webhook → Packagist (Settings → Integrations & services → Packagist)
- Test: `composer create-project easeo/cms-core test-site` in tijdelijke directory → download werkt
- Tag-propagatie: nieuwe tag in github → binnen 1-2 min op Packagist

### Task E9: Release-notes + changelog-finalize
- Alle `CHANGELOG.md` van "Unreleased" naar `[1.0.0] — 2026-MM-DD`
- GitHub Release aanmaken per tag met release-notes (gecombineerde changelog van alle plans)

### Task E10: Post-cutover monitoring (7 dagen)
- Dagelijks: check Apache error-log, healthcheck-endpoint, Lighthouse
- Dag 7: als geen issues → archiveer oude `/var/www/easeo-nl` structuur, verwijder rollback-mogelijkheid
- Feedback-loop: logs + issues die tijdens cutover boven komen → backlog voor volgende iteratie

---

## Go/no-go criteria voor productie-cutover
- [ ] Alle PHPUnit-tests pass (cms-core, shop-module, hello-module, tools)
- [ ] Staging draait 48u zonder errors
- [ ] Alle regressie-checks uit Task E4 groen
- [ ] Healthcheck-endpoint green op staging
- [ ] Rollback-runbook getest (eenmaal op staging: staging terugswitchen naar oude structuur binnen 2 min)
- [ ] DB-backup + file-backup van productie aanwezig
- [ ] Maintenance-window gecommuniceerd (indien toepasselijk)

---

## Open issues (te beslissen bij volledige uitwerking)
- Staging-URL: subdomein (staging.easeo.nl) of aparte TLD (staging-easeo.com)?
- Maintenance-window: no-down-time cutover via symlink-swap, of 5-min planned downtime?
- CI-runner: GitHub-hosted of self-hosted op Hostinger-VPS? (GitHub-hosted is makkelijker tot volume hoog wordt)
- Monitoring-stack: Hostinger ingebouwd, UptimeRobot, of eigen Grafana? (Out-of-scope dit plan — issue voor follow-up)

---
