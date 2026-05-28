# Changelog — easeo-cms monorepo

Wijzigingen aan de monorepo zelf (apps, tools, plans, specs). Voor wijzigingen aan het `easeo/cms-core` Composer-pakket: zie [`packages/cms-core/CHANGELOG.md`](packages/cms-core/CHANGELOG.md).

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) · Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

### Plan-status

- [x] **Plan 01** — Monorepo-skelet + PSR-4 namespace-refactor (alle Fase A + B tasks compleet, behalve `vendor-legacy/phpmailer/` cleanup die naar een latere plan verschuift)
- [ ] Plan 02 — Config-split (12-factor)
- [ ] Plan 03 — Module-infra + hello + shop-skelet
- [ ] Plan 04 — Deploy + cutover (gedeeltelijk gesuperseded door Plan 08)
- [x] **Plan 05** — cms-core CI + Packagist release-pipeline (A1-A5, A7-A12 compleet; A6 wacht op `App::boot()`)
- [x] **Plan 06** — `apps/_skeleton/` thin site-app template + `tools/instantiate-skeleton.php`
- [ ] Plan 07 — Bootstrap + lazy migrations in cms-core
- [ ] Plan 08 — `apps/easeo-website` cutover naar Capistrano-layout
- [ ] Plan 09 — Server-prep Capistrano runbook
- [ ] Plan 10 — `rww-website` cutover
- [ ] Plan 11 — `qpmarketing-website` cutover
- [ ] Plan 12 — Monitoring + knowledge tools

### Added
- AST-based `tools/rename-engine.php` voor herhaalbare engine-migraties
- `tools/instantiate-skeleton.php` voor het genereren van nieuwe klant-sites uit `apps/_skeleton/`
- `tools/check-changelog.php` als release-gate
- GitHub Actions release-workflow (`.github/workflows/release.yml`) met subtree-split naar `easeo-nl/cms-core` mirror → Packagist
- Branch-protection op `main` met required CI checks (geen direct-push, alleen PR-flow)

### Changed
- Repo geconverteerd naar monorepo: `packages/cms-core/` + `apps/easeo-website/` + `apps/_skeleton/` + `apps/_fixture-app/`
- `apps/_fixture-app/` als CI-only smoke-fixture (Plan 05)

### Removed
- `packages/cms-core/src/legacy/` (engines naar PSR-4 gemigreerd in Plan 01 B3-B16)
- `tools/legacy-bridge.php` (vervangen door `Easeo\Cms\Constants` class)
</content>
</invoke>