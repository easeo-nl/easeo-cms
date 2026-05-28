# Changelog — easeo/cms-core

Alle wijzigingen aan `easeo/cms-core` staan in dit bestand.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

## [0.1.0-rc2] - 2026-05-28

### Fixed
- `release.yml`: `contents: write` permission op release-notes job (rc1 faalde met 403 op GitHub release creation, zie PR #2)
- `release.yml`: vervang dode `splitsh-lite` v2.0.0 download door ingebouwde `git subtree split`
- Branch-protection actief op `main` met required CI checks

### Added
- CI badge in monorepo README
- `apps/_skeleton/` thin site-app template (Plan 06 B1 + B3)
- `docs/packagist-setup.md` en `docs/branch-protection.md` runbooks

## [0.1.0-rc1] - 2026-05-23

### Added
- Eerste release-candidate van cms-core na monorepo-split (Plans 01-04)
- CI-pipeline met PHPUnit matrix (PHP 8.1/8.2/8.3) + integration smoke op fixture-app
- Packagist-publicatie via splitsh/lite mirror-repo

### Schema impact
- N.v.t. — initial release

### Action voor site-beheerders
- N.v.t. — geen klant-installaties yet (eerste productie-cutover komt in Plan 08)
