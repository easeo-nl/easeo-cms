# Plan 12 — Monitoring + knowledge tools voor jr-devs (Fase H)

> **Status:** Skeleton. Kan parallel met Plan 08-11 uitgevoerd worden zodra Plan 05-07 klaar zijn; tools en docs blokkeren geen cutovers.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Lever de operationele tooling en kennisoverdracht-mechanismen uit spec sectie 8 + 8b: `bin/easeo-doctor`, `bin/easeo-explain`, `/healthcheck.php` endpoint, UptimeRobot/betterstack monitoring, ADR-skelet in elk klant-repo, `bin/easeo-check-client-versions` admin-tool voor Nick. Verlaagt operationele drempel voor Ardo en toekomstige jr-devs.

**Architectuur:**
- `bin/easeo-doctor` (in cms-core, composer-bin) verzamelt status: cms-core versie, Dependabot PR-count, laatste deploy, site-health (HTTP + GTM-check), schema-versie, backup-age, disk-usage shared/. Output is een afgevinkt-lijstje met "volgende acties". Werkt zowel lokaal (vanuit klant-repo) als remote (SSH wrapper).
- `bin/easeo-explain <topic>` (in cms-core) cat'ed markdown-bestanden uit `packages/cms-core/explain/` met ANSI-formatting. Topics: deploys, migrations, backups, dependabot, state, rollback, troubleshooting.
- `public/healthcheck.php` (toegevoegd in cms-core App-routes): JSON-endpoint met cms-core version, schema-version, writable-checks, en (verberg achter auth/secret in prod) recent error-log tail.
- UptimeRobot of betterstack als externe monitor; gratis tier dekt 50 sites op 5-min interval. Opzet via web-UI, gedocumenteerd in runbook.
- `bin/easeo-check-client-versions` (in monorepo `tools/`): Nick's tool dat Packagist API + GitHub API (klant-composer.lock files) vergelijkt; rapporteert welke klanten achterlopen op welke versie.

**Tech Stack:** PHP CLI met ANSI escape codes voor formatting, `curl` + `jq` voor remote health-checks, GitHub API + Packagist API (publieke endpoints, geen auth nodig), UptimeRobot web-UI (handmatige config).

**Afhankelijkheden:** Plan 05 (cms-core gepublished, composer-bin werkt), Plan 06 (skeleton heeft `bin/easeo-doctor` wrapper). Volledig nuttig pas na Plan 10/11 (twee klant-sites om tegen te draaien).

**Afbakening:** Géén nieuwe runtime-features in cms-core (geen module-systeem-uitbreidingen). Tools en docs only. Sentry-PHP-SDK integratie als optionele follow-up (niet in dit plan).

---

## Bestandsstructuur

**Aangemaakt:**
- `packages/cms-core/bin/easeo-doctor` — CLI entry
- `packages/cms-core/src/Doctor/Diagnostics.php` — verzamelt status-checks
- `packages/cms-core/src/Doctor/Renderer.php` — ANSI-formatted output
- `packages/cms-core/src/Doctor/Checks/{CmsCoreVersion,DependabotPrs,LastDeploy,SiteHealth,SchemaVersion,BackupAge,DiskUsage}.php` — één check per file
- `packages/cms-core/tests/Doctor/DiagnosticsTest.php`
- `packages/cms-core/bin/easeo-explain` — CLI entry
- `packages/cms-core/src/Explain/Explainer.php` — laadt topic, renderert
- `packages/cms-core/explain/deploys.md`
- `packages/cms-core/explain/migrations.md`
- `packages/cms-core/explain/backups.md`
- `packages/cms-core/explain/dependabot.md`
- `packages/cms-core/explain/state.md`
- `packages/cms-core/explain/rollback.md`
- `packages/cms-core/explain/troubleshooting.md`
- `packages/cms-core/tests/Explain/ExplainerTest.php`
- `packages/cms-core/src/Routes/Healthcheck.php` — handlert `/healthcheck.php` of `/healthcheck`
- `packages/cms-core/tests/Routes/HealthcheckTest.php`
- `tools/check-client-versions.php` — monorepo admin-tool
- `tools/tests/CheckClientVersionsTest.php`
- `docs/runbooks/uptimerobot-setup.md`
- `docs/runbooks/sentry-php-sdk-followup.md` — optionele follow-up, gemarkeerd als out-of-scope dit plan

**Gewijzigd:**
- `packages/cms-core/composer.json` — `bin` array uitgebreid met `bin/easeo-doctor` en `bin/easeo-explain`
- `packages/cms-core/src/App.php` — route-registratie voor `/healthcheck.php`
- `packages/cms-core/CHANGELOG.md` — entries voor doctor, explain, healthcheck
- Skeleton (`apps/_skeleton/bin/easeo-doctor`) en bestaande klant-repos: convenience-wrappers updaten (mogelijk no-op, was al een wrapper sinds Plan 06 B8)

---

## Tasks-outline

### Task H1: Doctor — Diagnostics class + één check
TDD: begin met `CmsCoreVersionCheck`. Test dat hij de juiste versie returneert vanuit `vendor/composer/installed.json`.

### Task H2: Doctor — alle checks implementeren
Voor elke check: TDD met mock-data of fixture. Eind: 7 checks, allemaal individueel testbaar.

### Task H3: Doctor — Renderer met ANSI
Test dat output afgevinkt-lijstje produceert. ANSI color codes voor groen/oranje/rood. Detect TTY → no-color als gepiped.

### Task H4: Doctor — CLI entry `bin/easeo-doctor`
Composeert alles. Run handmatig in fixture-app, snapshot output in CHANGELOG voor demo.

### Task H5: Doctor — remote-mode (SSH wrapper)
Optionele `--remote=<user@host:port>` flag die over SSH op de server een health-check doet. Geen privileges nodig — alleen `curl localhost/healthcheck.php`.

### Task H6: Explain — Explainer class + topic-loader
TDD: load topic, verify markdown content + ANSI rendering.

### Task H7: Explain — schrijf 7 topic-bestanden
Per topic: ~30-50 regels. Stijl: bondig, voorbeelden uit echte klant-context (rww/qp).

### Task H8: Explain — CLI entry + topic-discovery
`easeo-explain` zonder arg → list topics. `easeo-explain deploys` → toon content. `easeo-explain unknown-topic` → suggest closest topic via levenshtein.

### Task H9: Healthcheck endpoint
TDD: HTTP GET `/healthcheck` returnt JSON met version/schema-version/writable-checks. Voor security: alleen full info als `?secret=<HEALTHCHECK_SECRET>` query-param matched; anders alleen `{"status":"ok"}` of `{"status":"degraded"}`.

### Task H10: UptimeRobot setup runbook
Handmatige stappen: account, monitors voor easeo.nl + rwwbouw.nl + qpmarketing.nl, email/Slack alerts naar Nick + Ardo, public status page link.

### Task H11: check-client-versions tool
Monorepo admin-tool. Input: geen (default scant alle `easeo-nl/*-website` repos). Output: tabel van site → installed cms-core version → latest → "X versies achter". TDD met mock GitHub API + mock Packagist API.

### Task H12: CHANGELOG + tag v1.x
Eerste release met operational tooling. Geen schema-impact, gewoon nieuwe `bin/`-scripts.

### Task H13: Documentatie-update — klant-repo's
In bestaande rww + qpmarketing repos: update README.md met "Run `bin/easeo-doctor` voor status, `bin/easeo-explain <topic>` voor uitleg". Voeg toe via Dependabot-style PR die je zelf opent.

---

## Plan-status

| Task | Status |
|---|---|
| H1 — Diagnostics + eerste check | ☐ |
| H2 — alle 7 checks geïmplementeerd | ☐ |
| H3 — Renderer met ANSI | ☐ |
| H4 — easeo-doctor CLI entry | ☐ |
| H5 — doctor remote-mode | ☐ |
| H6 — Explainer + topic-loader | ☐ |
| H7 — 7 explain-topics geschreven | ☐ |
| H8 — easeo-explain CLI + suggest | ☐ |
| H9 — healthcheck endpoint | ☐ |
| H10 — UptimeRobot runbook + setup | ☐ |
| H11 — check-client-versions tool | ☐ |
| H12 — CHANGELOG + release | ☐ |
| H13 — docs-update in klant-repo's | ☐ |
