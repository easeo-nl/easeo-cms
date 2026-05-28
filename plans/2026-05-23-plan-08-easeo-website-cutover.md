# Plan 08 — easeo-website cutover naar nieuwe stack (Fase D)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 06 + 07 afgerond zijn (skelet + bootstrap/migrations beschikbaar als bouwstenen).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Migreer `apps/easeo-website` (de bestaande site-app uit Plan 01-03) naar het thin-site-app pattern uit Plan 06, deploy hem op de Capistrano-style server-layout (Plan 09), en gebruik cms-core via `composer require easeo/cms-core: ^0.x` in plaats van path-repo symlink (productie-modus). Eigen site eerst — bewijst de stack op productie vóór klanten erop gaan.

**Supersedes:** `2026-04-18-plan-04-deploy-en-cutover.md` (skeleton). Dit plan vervangt de deployment-secties van Plan 04 met de nieuwe Capistrano + symlink-shared + lazy-migrations aanpak. Plan 04's andere doelen (Packagist publicatie van shop-module) worden separaat opgepakt in een toekomstig plan.

**Architectuur:**
- `apps/easeo-website/` in de monorepo blijft als ontwikkel-omgeving (composer path-repo symlink naar `packages/*`, snel iteratief werken aan core).
- Productie-deploy gebruikt **niet** de monorepo direct: deploy-pipeline pakt `apps/easeo-website/` plus een aparte `composer.deploy.json` waarin `easeo/cms-core` als Packagist-dependency staat in plaats van path-repo.
- Server-layout volgens spec sectie 5 (Capistrano: releases/, shared/, current symlink).
- Cutover via DocumentRoot-switch (Apache) van oude `public_html/` naar nieuwe `public_html/current/public/`.

**Tech Stack:** Composer 2.x, GitHub Actions, rsync/scp, Apache .htaccess, bash, lazy migrations runtime (Plan 07).

**Afhankelijkheden:** Plan 05 (cms-core op Packagist), Plan 06 (skeleton als template), Plan 07 (bootstrap + runner), Plan 09 (server-prep moet eerst gebeuren).

**Afbakening:** Géén klant-sites (rww, qpmarketing) in dit plan — die komen in Plan 10/11. Géén nieuwe features in easeo-website inhoud; alleen infrastructuur-cutover.

---

## Bestandsstructuur

**Aangemaakt:**
- `apps/easeo-website/composer.deploy.json` — productie composer.json zonder path-repo (alleen Packagist)
- `apps/easeo-website/bin/build-deploy-tarball.sh` — bouwt deploy-tarball met composer.deploy.json
- `apps/easeo-website/.github/workflows/deploy.yml` — productie deploy (geen, monorepo heeft één deploy.yml met `paths:` filter op `apps/easeo-website/**`)
- `.github/workflows/deploy-easeo-website.yml` — push op main + paths-filter triggert deploy van easeo.nl
- `apps/easeo-website/docs/CUTOVER-CHECKLIST.md` — pre-, tijdens-, en post-cutover stappen

**Gewijzigd:**
- `apps/easeo-website/composer.json` — blijft path-repo voor lokaal/CI; **niet** voor productie
- `apps/easeo-website/public/index.php` — vervang oude entry door 3-regel boot uit Plan 06 pattern
- `apps/easeo-website/.gitignore` — synchroniseer met spec sectie 2
- `apps/easeo-website/site.template.json` — verfijn met productie-defaults (lege GTM, branding placeholder)
- `packages/cms-core/CHANGELOG.md` — markeer eerste productie-deployment van core

---

## Tasks-outline

### Task D1: composer.deploy.json + build-tarball script
TDD: shell-test dat `build-deploy-tarball.sh` een tarball produceert die (a) géén `data/` bevat, (b) géén `vendor/` bevat (composer install runt op server of in CI-build), (c) composer.json (deploy-versie) bevat met `easeo/cms-core: ^0.x` zonder path-repo.

### Task D2: GitHub Actions deploy-easeo-website.yml
Implementeer workflow exact volgens spec sectie 5 (build + deploy + smoke jobs). Path-filter: `apps/easeo-website/**`, `packages/cms-core/**` (laatste triggert ook deploy zodat core-bumps direct landen na CI groen). Concurrency-group `deploy-easeo-website`.

### Task D3: Smoke-test voor easeo.nl
Specifiek: homepage 200, GTM-ID `GTM-XXXX` (de echte) aanwezig in HTML, `/sitemap.xml` 200, contact-form GET 200. Faal-output toont eerste 50 regels HTML voor diagnose.

### Task D4: Pre-cutover backup-script (one-time)
`bin/pre-cutover-backup.sh` SSH't naar server en tar-zip't huidige `data/` + `images/uploads/` naar `~/backups/easeo-pre-cutover-YYYY-MM-DD.tgz`. Test met dry-run.

### Task D5: Cutover-checklist document
Schrijf `docs/CUTOVER-CHECKLIST.md`. Bevat: pre-cutover backups, DNS-status-snapshot, server-restructure-bevestiging (Plan 09 done), eerste deploy-trigger, smoke-pass verifiëren, rollback-trigger (DocumentRoot terug naar legacy), 24u observatie-window, legacy-cleanup.

### Task D6: Test-deploy op staging-domain
Voor live-cutover: deploy naar `staging.easeo.nl` (subdomein op zelfde VPS, eigen vhost). Smoke-test daar. Pas live na 24u green.

### Task D7: Live cutover easeo.nl
Apache DocumentRoot switch via .htaccess of vhost-config. ~10 sec downtime. Smoke direct erna. Annotation in monitoring (UptimeRobot) voor incident-correlation.

### Task D8: 48u observatie + legacy cleanup
Geen rollback nodig → `~/domains/easeo.nl/releases/legacy-*` verwijderen, schijfruimte vrijmaken. Eindstand: alleen Capistrano-layout.

### Task D9: CHANGELOG + tag v1.0.0
Eerste **stable** release van cms-core. Tag triggert Packagist publish (geen rc-suffix dit keer). Klant-repo composer.json's kunnen nu op `^1.0` pinnen.

---

## Plan-status

| Task | Status |
|---|---|
| D1 — composer.deploy.json + build-tarball | ☐ |
| D2 — GitHub Actions deploy workflow | ☐ |
| D3 — smoke test easeo.nl | ☐ |
| D4 — pre-cutover backup script | ☐ |
| D5 — cutover-checklist document | ☐ |
| D6 — test-deploy op staging-subdomain | ☐ |
| D7 — live cutover easeo.nl | ☐ |
| D8 — 48u observatie + legacy cleanup | ☐ |
| D9 — CHANGELOG + tag v1.0.0 stable | ☐ |
