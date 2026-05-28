# Plan 07 — Bootstrap-script + lazy migrations in cms-core (Fase C)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 05 + 06 afgerond zijn (skeleton bestaat als ground-truth voor bootstrap-target shape).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Voeg aan cms-core twee runtime-mechanismen toe: (a) `bin/easeo-bootstrap` voor first-deploy state-initialisatie, (b) `Easeo\Cms\Migration\Runner` voor lazy schema-migrations bij elke web-request. Beide werken op Hostinger shared hosting (geen daemons, geen cron-vereisten).

**Architectuur:**
- `bin/easeo-bootstrap` is een composer-`bin` script. Detecteert ontbreken van `data/site.json` → kopieert `site.template.json` → `data/site.json`, schrijft `data/.schema-version` met huidige cms-core versie, maakt skelet-files voor `pages`, `posts`, `navigation`, `users`. Idempotent.
- `Easeo\Cms\Migration\Runner` wordt aangeroepen vanuit `Easeo\Cms\App::boot()`. Scant `packages/cms-core/migrations/`, vergelijkt met `data/.schema-version`, runt pending migrations met `flock` voor concurrency-safety. Forward-only.
- `Easeo\Cms\Migration\MigrationInterface` definieert het contract (`version(): int`, `description(): string`, `up(ContentRepository): void`).
- Atomic file-writes via tmp+rename in `ContentRepository::save()` (mogelijke kleine refactor in Plan 02 of hier).

**Tech Stack:** PHP 8.2+, geen nieuwe deps. Glob voor migration-discovery, `flock` voor concurrency. PHPUnit voor unit tests; bash + curl voor end-to-end test in fixture-app.

**Afhankelijkheden:** Plan 05 (CI infrastructure aanwezig), Plan 06 (skeleton-template ready voor consumption van `bin/easeo-bootstrap` als composer-`bin`).

**Afbakening:** Geen `down()`-migrations (forward-only). Major-version upgrade-paths met data-vorm-wijzigingen krijgen apart `bin/easeo-pre-upgrade-check` per release; valt buiten dit plan.

---

## Bestandsstructuur

**Aangemaakt:**
- `packages/cms-core/bin/easeo-bootstrap` — CLI entry (PHP shebang)
- `packages/cms-core/src/Bootstrap/Bootstrapper.php` — logica achter `easeo-bootstrap`
- `packages/cms-core/src/Migration/MigrationInterface.php`
- `packages/cms-core/src/Migration/Runner.php` — scant + runt pending migrations
- `packages/cms-core/src/Migration/SchemaVersion.php` — wrapper rond `data/.schema-version`
- `packages/cms-core/migrations/0001_initial_skeleton.php` — garandeert basis-files bestaan (no-op als bootstrap is gedraaid)
- `packages/cms-core/migrations/README.md` — "hoe schrijf je een migration" voor toekomstige core-contributors
- `packages/cms-core/tests/Bootstrap/BootstrapperTest.php`
- `packages/cms-core/tests/Migration/RunnerTest.php`
- `packages/cms-core/tests/Migration/SchemaVersionTest.php`
- `packages/cms-core/tests/Migration/Fixtures/0001_test_migration.php`
- `packages/cms-core/tests/Migration/Fixtures/0002_test_migration.php`

**Gewijzigd:**
- `packages/cms-core/composer.json` — `bin` array: `["bin/easeo-bootstrap"]`
- `packages/cms-core/src/App.php` — `boot()` roept `Migration\Runner::runPending()` aan na bootstrap-check
- `packages/cms-core/src/Content/ContentRepository.php` — `save()` gebruikt atomic tmp+rename (als nog niet zo)
- `packages/cms-core/CHANGELOG.md` — `[Unreleased]` met `Schema impact` sectie
- `apps/_fixture-app/bin/seed.sh` (Plan 05) — eventueel aanpassen om met nieuw bootstrap-pad te werken

---

## Tasks-outline

### Task C1: SchemaVersion class + tests
TDD voor read/write van `data/.schema-version`. Default `0` als file niet bestaat. Atomic write.

### Task C2: MigrationInterface + Runner skelet + tests
Definieer interface, implementeer Runner die migrations scant uit `packages/cms-core/migrations/` (glob). Test met fixture-migrations in `tests/Migration/Fixtures/`.

### Task C3: Runner flock-based concurrency
TDD: twee parallelle Runner::runPending() calls, slechts één runt, andere bail-outt. Gebruik `flock(LOCK_EX | LOCK_NB)`. Test met `pcntl_fork` of Symfony Process voor parallelle execution.

### Task C4: ContentRepository atomic save (indien nog niet)
Audit huidige `save()`-implementatie. Als niet atomic → refactor naar tmp+rename. Test: kill-mid-write simulatie (timeout binnen save) laat oude file intact.

### Task C5: `bin/easeo-bootstrap` script + Bootstrapper class
Idempotent CLI. Detecteert ontbrekende data-files, kopieert template, initialiseert `.schema-version` met huidige cms-core versie (gelezen uit composer-installed-versions). Test: run twice, geen errors.

### Task C6: Wire bootstrap + runner in App::boot()
Modify App::boot() om Bootstrapper first te runnen (no-op if data exists), dan Runner::runPending(). Test in fixture-app: verwijder data/, request homepage, verifieer dat data/ wordt aangelegd en homepage rendert.

### Task C7: Eerste echte migration (0001_initial_skeleton)
Garandeert dat alle essentiële JSON-files bestaan met minimum-structuur. No-op na bootstrap, maar redundant safety-net voor sites waar bootstrap geskipt is.

### Task C8: Migration `audit.log` integration
Elke succesvolle migration logt naar `data/audit.log` met timestamp + version + description. Test.

### Task C9: Integration test in fixture-app
Voeg een dummy migration `0099_add_test_field.php` toe in fixture-app's eigen test-migration-dir. Run fixture-app, verify dat data wordt gemuteerd en `.schema-version` 99 wordt. Cleanup na test.

### Task C10: OPcache reset op version-mismatch
Wanneer App::boot() detecteert dat de huidige cms-core versie hoger is dan de vorige (vendor/composer/installed.json vergelijken met laatst-bekend), roep `opcache_reset()` aan. Voorkomt stale class-paths na symlink-deploy (spec sectie 9 risico 2).

### Task C11: CHANGELOG + tag-bump naar 0.2.0
Eerste minor-bump die nieuwe schema-changes-capability toevoegt. Update CHANGELOG met `Schema impact: none for fresh sites; existing sites krijgen 0001_initial_skeleton no-op`. Tag triggert Plan 05 release-pipeline → Packagist.

---

## Plan-status

| Task | Status |
|---|---|
| C1 — SchemaVersion + tests | ☐ |
| C2 — Migration interface + Runner skelet | ☐ |
| C3 — Runner concurrency met flock | ☐ |
| C4 — ContentRepository atomic save | ☐ |
| C5 — easeo-bootstrap CLI + Bootstrapper | ☐ |
| C6 — Wire in App::boot() | ☐ |
| C7 — Initial skeleton migration | ☐ |
| C8 — Migration audit.log | ☐ |
| C9 — Integration test fixture-app | ☐ |
| C10 — OPcache reset op version-mismatch | ☐ |
| C11 — CHANGELOG + release v0.2.0 | ☐ |
