# DEVELOPER.md — {{SITE_NAME}} website

Welkom. Je gaat werken aan een **thin site-app** voor {{SITE_DOMAIN}}, gebouwd op [easeo/cms-core](https://packagist.org/packages/easeo/cms-core).

## In 5 minuten

### Wat zit in deze repo?

- **`public/`** — Apache DocumentRoot + entry-point (`index.php` boot cms-core)
- **`templates/`** — site-eigen layout-overrides (header, footer, hero, …)
- **`images/`** — statische site-assets (logo, hero-foto's)
- **`site.template.json`** — default config-skelet (GTM-id, branding, SMTP); klant bewerkt z'n eigen kopie via het CMS
- **`bin/easeo-doctor`** — wrapper voor `vendor/bin/easeo-doctor` (status van je install)
- **`.github/workflows/`** — CI/CD: PR-check, deploy, Dependabot-comment

### Wat zit NIET in deze repo?

- **Geen `vendor/`** — komt via `composer install`
- **Geen `data/*.json`** — leeft op de server, beheerd via `{{SITE_DOMAIN}}/beheer/`
- **Geen `images/uploads/`** — geüpload via het CMS, leeft op de server
- **Geen core-code** — alle engines, beheer-UI, router, blogs, formulieren komen uit `vendor/easeo/cms-core/`

### Local dev

```bash
composer install
./bin/easeo-doctor
php -S localhost:8080 -t public
```

Open http://localhost:8080. Admin: `{{SITE_DOMAIN}}/beheer/`.

### Hoe komen updates van cms-core binnen?

`easeo/cms-core` releases een nieuwe versie → Dependabot opent automatisch een PR in deze repo → review checklist (auto-comment) → CI groen → merge → deploy.

Caret-pin (`^X`) in `composer.json` blokkeert breaking changes; majors vereisen een handmatige PR.

### Waar staat de waarom van architectuur-keuzes?

`docs/adr/` — vier korte records:
- ADR 0001 — thin site-app pattern (waarom geen core-code in deze repo)
- ADR 0002 — untracked state files (waarom geen `data/*.json` in git)
- ADR 0003 — symlink-based deploys (Capistrano-stijl atomic swap)
- ADR 0004 — lazy migrations (waarom geen migration-step in deploy)

### Vragen?

- Hoe deploy ik? → `docs/DEPLOY.md`
- Hoe roll ik terug? → `docs/DEPLOY.md` § Rollback
- Status van mijn install? → `./bin/easeo-doctor`
- Concept-uitleg per topic? → `./vendor/bin/easeo-explain <topic>` (na composer install)
- Echt vast? → ping @{{BACKSTOP_HANDLE}} of `easeo-nl/easeo-cms` issues
