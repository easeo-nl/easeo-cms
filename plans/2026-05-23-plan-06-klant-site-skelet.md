# Plan 06 — Klant-site skelet als `apps/_skeleton/` (Fase B)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 05 afgerond en op main gemerged is (CI + Packagist gevalideerd).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lever in de monorepo een `apps/_skeleton/`-template dat als bron dient voor alle nieuwe klant-sites en voor de migratie van bestaande sites (rww, qpmarketing). Het skelet is een werkende thin site-app die `composer require easeo/cms-core` doet en niets meer.

**Architectuur:** `apps/_skeleton/` is een **referentie-implementatie**, niet zelf een Packagist-package — het is een tarball-bron dat klant-repos kopiëren bij hun cutover (Plan 08, 10, 11). Structuur identiek aan wat in spec sectie 1 beschreven staat: `public/index.php`, `templates/`, `images/`, `site.template.json`, `data/.gitkeep`, `bin/easeo-doctor` wrapper, `.github/workflows/{deploy,pr-check}.yml`, `.github/dependabot.yml`, `.github/PULL_REQUEST_TEMPLATE.md`, `docs/{DEVELOPER,DEPLOY}.md`, `docs/adr/`, `.gitignore`, `CHANGELOG.md`, `README.md`.

**Tech Stack:** PHP 8.2+, composer 2.x, GitHub Actions templates, geen nieuwe dependencies. Templates zijn `.example`-stijl bestanden die per klant-cutover ingevuld worden (site_name, deploy_path, etc.).

**Afhankelijkheden:** Plan 05 (Packagist publicatie werkt; `composer require easeo/cms-core` werkt vanuit een lege project-dir).

**Afbakening:** Plan 06 levert het skelet en valideert dat het lokaal werkt met een fictieve "site". Échte klant-migratie komt in Plan 08+. Bootstrap-script + lazy migrations zitten in Plan 07.

---

## Bestandsstructuur

**Aangemaakt:**
- `apps/_skeleton/composer.json` — vereist `easeo/cms-core: ^1.0` (na eerste echte 1.0 release; voor nu `^0.1`)
- `apps/_skeleton/public/index.php` — 3-regel boot
- `apps/_skeleton/public/.htaccess` — Apache rewrite, security headers
- `apps/_skeleton/templates/.gitkeep`
- `apps/_skeleton/images/.gitkeep`
- `apps/_skeleton/data/.gitkeep`
- `apps/_skeleton/site.template.json.example` — placeholder values (`{{SITE_NAME}}`, `{{GTM_ID}}`)
- `apps/_skeleton/.gitignore` — `data/*.json`, `images/uploads/`, `vendor/`, `.env`, `composer.lock` (NIET — die hoort er WEL in voor reproducible builds; check spec sectie 1)
- `apps/_skeleton/bin/easeo-doctor` — 1-regel wrapper `exec vendor/bin/easeo-doctor "$@"`
- `apps/_skeleton/.github/workflows/deploy.yml` — template met placeholders voor secrets-names
- `apps/_skeleton/.github/workflows/pr-check.yml` — composer install + lint + dry-run op PR
- `apps/_skeleton/.github/dependabot.yml` — easeo/* allowed, weekly schedule, NL/Europe/Amsterdam tz
- `apps/_skeleton/.github/PULL_REQUEST_TEMPLATE.md`
- `apps/_skeleton/.github/workflows/dependabot-comment.yml` — auto-comment met cms-core review checklist op elke easeo/* PR
- `apps/_skeleton/docs/DEVELOPER.md` — 5-min onboarding (spec sectie 8b)
- `apps/_skeleton/docs/DEPLOY.md` — deploy + rollback + troubleshooting
- `apps/_skeleton/docs/adr/0001-thin-site-app-pattern.md`
- `apps/_skeleton/docs/adr/0002-untracked-state-files.md`
- `apps/_skeleton/docs/adr/0003-symlink-based-deploys.md`
- `apps/_skeleton/docs/adr/0004-lazy-migrations.md`
- `apps/_skeleton/CHANGELOG.md` — Keep-a-Changelog format, `[Unreleased]` only
- `apps/_skeleton/README.md` — links naar docs/ + "hoe deploy ik?" oneliner
- `tools/instantiate-skeleton.php` — CLI: `php tools/instantiate-skeleton.php --site=rww --domain=rwwbouw.nl` schrijft een geïnstantieerde versie naar `/tmp/<site>-bootstrap/` voor klant-cutover
- `tools/tests/InstantiateSkeletonTest.php`

**Gewijzigd:**
- `composer.json` (root) — voeg `apps/_skeleton` toe aan de path-repository search (geen, want het is geen package)
- `.gitignore` — geen change (apps/* al covered door wildcard)

---

## Tasks-outline

### Task B1: Skelet-directory + composer.json + entry-files
Plaats minimale werkende boot. Verifieer dat `composer install && php -S localhost:8080 -t public/` werkt met een dummy `data/site.json` handmatig geplaatst.

### Task B2: `.gitignore` + state-scheiding setup
Schrijf de definitieve `.gitignore` (uit spec sectie 2). Test dat `git status` na een fictieve CMS-edit van `data/site.json` géén untracked file toont.

### Task B3: GitHub Actions templates (deploy + pr-check)
Implementeer `deploy.yml` exact zoals spec sectie 5. Gebruik placeholder `<SITE_DOMAIN>` voor smoke-test URL. PR-check is light: composer install + lint, geen deploy.

### Task B4: Dependabot config + auto-comment workflow
Implementeer `dependabot.yml` + `dependabot-comment.yml`. De auto-comment workflow detecteert `easeo/*` PR's en post een review-checklist als comment (gebruikt `gh` CLI via `GITHUB_TOKEN`).

### Task B5: PR-template + ADR-bestanden
Vier ADRs schrijven volgens Michael Nygard format. PR-template uit spec sectie 8b.

### Task B6: DEVELOPER.md + DEPLOY.md
Onboarding-docs schrijven. DEPLOY.md bevat: hoe rollback (drie SSH-commands), hoe troubleshoot smoke-faal, links naar `bin/easeo-doctor` en `bin/easeo-explain`.

### Task B7: README.md
Top-level overview, korte links naar docs/, badge-placeholders voor CI + cms-core versie.

### Task B8: `bin/easeo-doctor` wrapper
1-regel script. Test dat het correct exec naar `vendor/bin/easeo-doctor` doet (vereist Plan 12 voor de echte doctor; nu placeholder die "doctor coming in Plan 12" print).

### Task B9: `tools/instantiate-skeleton.php` + tests
TDD-stijl: tests eerst (geneert verwachte file-list met geïnstantieerde placeholders), dan implementatie. CLI vervangt `{{SITE_NAME}}`, `{{SITE_DOMAIN}}`, `{{DEPLOY_PATH}}`, `{{GITHUB_HANDLE_REVIEWER}}`, `{{GITHUB_HANDLE_BACKSTOP}}`.

### Task B10: End-to-end skeleton-instantiation test
Run `instantiate-skeleton.php` met fictieve klant `demo-site`. Verifieer in `/tmp/demo-site-bootstrap/`: composer install werkt, lokaal opstarten werkt met dummy data, geen onverwerkte placeholders meer.

### Task B11: Documentatie in monorepo README + plan-completion
Voeg sectie toe aan monorepo README: "Hoe creëer ik een nieuwe klant-site?" met verwijzing naar `tools/instantiate-skeleton.php`.

---

## Plan-status

| Task | Status |
|---|---|
| B1 — skelet-dir + composer + entry-files | ☐ |
| B2 — .gitignore + state-scheiding | ☐ |
| B3 — GitHub Actions templates | ☐ |
| B4 — Dependabot config + auto-comment | ☐ |
| B5 — PR-template + ADRs | ☐ |
| B6 — DEVELOPER + DEPLOY docs | ☐ |
| B7 — README | ☐ |
| B8 — easeo-doctor wrapper | ☐ |
| B9 — instantiate-skeleton CLI + tests | ☐ |
| B10 — end-to-end instantiation test | ☐ |
| B11 — monorepo README + plan-completion | ☐ |
