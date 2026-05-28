# ADR 0002 — Untracked state files (data/*.json)

**Date:** 2026-05-23
**Status:** Accepted

## Context

Het CMS schrijft direct naar JSON-bestanden in `data/` (site.json met GTM-id en branding, pages.json, posts.json, navigation.json, …). Vóór deze ADR waren die files in git getrackt. Resultaat: bij `git pull` na een wijziging via het CMS verdwijnt de live state (GTM-id weg, content weg).

## Decision

Alle `data/*.json` bestanden staan in `.gitignore`. Alleen `site.template.json` (default skelet) staat in git. Bij eerste deploy bootstrapt cms-core `data/site.json` uit de template (Plan 07).

Op de server zijn `data/` en `images/uploads/` symlinks naar `~/domains/<site>/shared/`. Elke deploy is een nieuwe release-directory; de symlinks blijven naar dezelfde shared data wijzen (Capistrano-stijl).

## Consequences

**Positief:**
- Code-deploys raken nooit klant-state. GTM-id, content, branding blijven gegarandeerd intact
- pr-check workflow gate voorkomt dat iemand per ongeluk `git add -f data/*.json` doet
- Backups van klant-state zijn één tarball van `shared/` (no git history pollution)

**Negatief / acceptable:**
- Schema-changes in cms-core vereisen lazy migrations (Plan 07) i.p.v. git-tracked migrations
- Eerste deploy op een verse server vereist een `bin/easeo-bootstrap` call om `data/` te initialiseren

## Referenties

- Design spec § Sectie 2
- ADR 0003 (symlink deploys) voor server-layout
- ADR 0004 (lazy migrations) voor schema-evolutie
