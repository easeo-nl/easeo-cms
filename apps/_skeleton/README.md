# {{SITE_NAME}} website

Site-app voor [{{SITE_DOMAIN}}](https://www.{{SITE_DOMAIN}}), gebouwd op [easeo/cms-core](https://packagist.org/packages/easeo/cms-core).

> **Thin site-app pattern.** Deze repo bevat alleen branding, templates en deploy-config. Alle CMS-functionaliteit komt uit `vendor/easeo/cms-core/` via Composer. Zie [ADR 0001](docs/adr/0001-thin-site-app-pattern.md) voor het waarom.

## Quickstart

```bash
composer install --no-dev --optimize-autoloader
./bin/easeo-doctor                          # status
php -S localhost:8080 -t public             # lokaal opstarten
```

Open http://localhost:8080. Admin: http://localhost:8080/beheer/.

## Documentation

- [`docs/DEVELOPER.md`](docs/DEVELOPER.md) — 5-min onboarding voor nieuwe devs
- [`docs/DEPLOY.md`](docs/DEPLOY.md) — deploy + rollback + troubleshooting
- [`docs/adr/`](docs/adr/) — architectuur-beslissingen

## Updates

`easeo/cms-core` updates landen automatisch als Dependabot-PRs (wekelijks). Review checklist staat als auto-comment op elke PR. Merge → CI deployt automatisch → smoke-test verifieert dat het GTM-script in de HTML staat en de homepage HTTP 200 geeft.

## Operationeel

- **Diagnostiek:** `./bin/easeo-doctor`
- **Concept-uitleg:** `./vendor/bin/easeo-explain <topic>` (deploys, migrations, backups, dependabot, state, rollback, troubleshooting)
- **CMS-beheer:** https://www.{{SITE_DOMAIN}}/beheer/
- **Issues + core-PRs:** [easeo-nl/easeo-cms](https://github.com/easeo-nl/easeo-cms)
