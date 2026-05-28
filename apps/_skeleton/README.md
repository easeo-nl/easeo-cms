# {{SITE_NAME}} website

Site-app voor {{SITE_DOMAIN}}, gebouwd op [easeo/cms-core](https://packagist.org/packages/easeo/cms-core).

> Dit is een **thin site-app**: alleen branding, templates en deploy-config. Alle CMS-functionaliteit komt uit `vendor/easeo/cms-core/` via Composer.

## Quickstart

```bash
composer install --no-dev --optimize-autoloader
./bin/easeo-doctor          # status van je installatie
```

## Documentation

- [`docs/DEVELOPER.md`](docs/DEVELOPER.md) — 5-min onboarding voor nieuwe devs
- [`docs/DEPLOY.md`](docs/DEPLOY.md) — deploy + rollback + troubleshooting
- [`docs/adr/`](docs/adr/) — architectuur-beslissingen (waarom symlinks, waarom untracked state, etc.)

## Updates

`easeo/cms-core` updates komen automatisch als Dependabot-PRs. Review notes, merge — CI deployt automatisch.
