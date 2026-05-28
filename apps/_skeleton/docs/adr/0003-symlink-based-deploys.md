# ADR 0003 — Symlink-based deploys (Capistrano-stijl)

**Date:** 2026-05-23
**Status:** Accepted

## Context

Een naïeve git-pull deploy op de webroot betekent dat de site mid-update inconsistent kan zijn (PHP files veranderen terwijl een request loopt). Daarnaast: een failed deploy laat de site in een halve staat achter, zonder snelle rollback.

## Decision

Server-layout per site:

```
~/domains/<site>/
├── public_html → releases/<sha>/public      # Apache DocumentRoot, symlink
├── releases/
│   ├── <sha1>/
│   ├── <sha2>/
│   └── ...                                  # behoud laatste 5
└── shared/
    ├── data/                                # alle CMS-state
    └── images/uploads/                      # alle uploads
```

GitHub Actions deploy: upload tarball naar `releases/<sha>/`, symlink shared/ daarin, atomic swap van `public_html → releases/<sha>/public` met `mv -Tf`. Tarball excludeert `data/` en `images/uploads/` — fysiek onmogelijk om state te overschrijven.

Rollback: `ln -sfn releases/<vorige-sha>/public public_html`. Eén SSH-commando, <10 sec downtime.

## Consequences

**Positief:**
- Zero-downtime atomic swap (geen halve staten)
- Rollback is één commando
- Failed deploys laten vorige release ongemoeid (fail-safe)
- Disk-usage onder controle (5 releases × tarball size)

**Negatief / acceptable:**
- Vereist eenmalige server-prep (Plan 09): directory-restructure naar releases/+shared/+symlink
- Vereist dat de hosting-omgeving symlinks ondersteunt — Hostinger shared OK
- PHP OPcache moet timestamps valideren (`opcache.validate_timestamps=1`, Hostinger default) of `opcache_reset()` op version-mismatch

## Referenties

- Design spec § Sectie 5 + Sectie 7
- Plan 09 voor server-prep runbook
