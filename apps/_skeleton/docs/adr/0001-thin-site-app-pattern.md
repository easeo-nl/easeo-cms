# ADR 0001 — Thin site-app pattern

**Date:** 2026-05-23 (skeleton-template, geldig vanaf instantiatie per klant)
**Status:** Accepted

## Context

Klant-sites (rww-website, qpmarketing-website, …) waren historisch forks van het complete `easeo-cms` codebase. Updates vereisten handmatige merges per klant, en dezelfde core-code stond in elke klant-repo, divergerend over tijd.

## Decision

Klant-repo's bevatten alleen wat site-eigen is: branding, templates, statische assets, deploy-config. Alle core-functionaliteit (engines, beheer-UI, router, template-base) komt uit `vendor/easeo/cms-core/` via Composer. De klant-repo doet één regel `composer require easeo/cms-core: ^X.Y`.

Voor template-resolutie: cms-core zoekt eerst in `<site-root>/templates/`, dan in `vendor/easeo/cms-core/templates/`. Site-overrides winnen, core is fallback. Geen merge-conflicts mogelijk want klant raakt core nooit aan.

## Consequences

**Positief:**
- Updates aan core stromen via Packagist + Dependabot, niet via git-merge per klant
- Klant-repo's blijven klein (~20 files i.p.v. ~200), code-review wordt sneller
- Caret-pin (`^X`) beschermt klanten tegen breaking changes via semver

**Negatief / acceptable:**
- Vereist dat klant-devs Composer begrijpen (minor learning curve voor jr-devs)
- Een breaking change in core vereist een major-bump van cms-core + handmatige PR per klant (Dependabot blokkeert majors via caret)

## Referenties

- Design spec: `2026-05-23-easeo-cms-cicd-en-update-flow-design.md` § Sectie 1
