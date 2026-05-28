# ADR 0004 — Lazy migrations bij first-request

**Date:** 2026-05-23
**Status:** Accepted

## Context

cms-core evolueert: `site.json` krijgt nieuwe velden, `pages.json` schema verandert. Op klant-sites moet de live data automatisch mee-migreren bij een upgrade van cms-core, zonder dat de klant of Ardo daar iets voor hoeft te doen.

Hostinger shared hosting heeft geen daemons of cron met root-access. We kunnen geen migration-runner aan de serverkant draaien.

## Decision

`cms-core` ships migration-bestanden in `packages/cms-core/migrations/` (genummerd: `0001_*.php`, `0002_*.php`, …). `Easeo\Cms\App::boot()` runt bij elke web-request een `Migration\Runner`:

1. Lees `data/.schema-version`
2. Scan beschikbare migrations
3. Filter op `version > current`
4. flock `data/.migration.lock` (LOCK_EX | LOCK_NB) — andere parallel-request bail-outt
5. Run pending `up()` methodes, bump version per migration
6. Release lock

Forward-only (geen `down()`). Idempotent dankzij version-check + flock. Geen cron of daemon nodig.

## Consequences

**Positief:**
- Klant-deploys vereisen geen aparte migration-step
- Eerste request na deploy doet de migration (~<100ms typisch)
- Concurrency-safe via flock (parallel requests serialize zonder data corruption)
- Audit-trail in `data/audit.log`

**Negatief / acceptable:**
- Eerste request na major upgrade is wat trager (één keer per deploy)
- Migration-failures leiden tot een 500-error op die request; tweede request probeert opnieuw
- Major-version migrations (breaking schema) vereisen handmatige pre-upgrade check + backup; lazy is alleen veilig voor additive changes

## Referenties

- Design spec § Sectie 6
- Plan 07 voor implementatie van Bootstrapper + Migration\Runner
- ADR 0002 (untracked state) voor waarom files-based migrations passen bij file-based state
