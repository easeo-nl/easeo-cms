---
title: easeo-cms CI/CD en update-flow — parent→child zonder klant-state te slopen
date: 2026-05-23
status: approved
author: Nick Aldewereld
depends_on: 2026-04-17-easeo-cms-monorepo-split-design.md
---

# easeo-cms CI/CD en update-flow

## Context & probleem

`easeo-cms` is een file-based PHP-CMS. Klantsites zijn nu **forks** van easeo-cms (`rww-website`, `qpmarketing-website`). Wijzigingen via het beheer-paneel (Google Tag Manager-ID, branding, content) worden weggeschreven naar `data/*.json`-bestanden die in de huidige klant-repo's getrackt zijn in git.

Concreet probleem: als een dev (Ardo, jr) `git pull` doet op de server na een release, overschrijft de pull `data/site.json` en daarmee verdwijnt de live GTM-ID, branding, en andere CMS-state. RWW's `DEPLOY.md` documenteert dit al expliciet als bekende beperking; QP's deploy script omzeilt het via rsync-excludes maar valt om zodra iemand op de server `git pull` doet.

**Doel:** code-updates van `easeo/cms-core` (Nick beheert) stromen voorspelbaar en zonder hand-werk naar klantsites (Ardo deployt), en klant-state blijft altijd intact, ongeacht welke push waar binnenkomt.

**Bedoelde gebruikers van dit systeem:**
- **Nick**: maintainer van `easeo/cms-core`, 2e/3e lijns support, wil zo min mogelijk afhankelijkheid in klant-flows
- **Ardo (jr dev)**: deploys op klant-sites, mergt Dependabot PR's, kan zelfstandig debuggen met goede docs
- **Sylvester (online marketeer)**: gebruikt het CMS voor content/GTM, deployt niet
- **Toekomstige onboarding-devs**: leren `easeo-cms` via de klant-repo docs

**Scope:** dit ontwerp komt **na** de monorepo-cutover van Plan 04. Het beschrijft de eindstand én het migratie-pad voor de twee bestaande klantsites.

## Architectuur — overzicht

```
┌─ easeo-nl/easeo-cms (monorepo, jij beheert)
│   ├─ packages/cms-core    → composer-pakket, gepubliceerd op publieke Packagist
│   ├─ packages/hello-module
│   ├─ packages/shop-module (parked)
│   ├─ apps/easeo-website   → eigen site (easeo.nl)
│   ├─ apps/qpmarketing-website → referentie-implementatie (genesis, zie open vragen)
│   └─ apps/_fixture-app    → integration-test fixture (alleen CI)
│
├─ easeo-nl/rww-website (standalone, Ardo deployt)
│   └─ composer require easeo/cms-core: ^1.0
│
└─ easeo-nl/qpmarketing-website (standalone, Ardo deployt)
    └─ composer require easeo/cms-core: ^1.0
```

**Update-flow:**

```
Nick pusht naar cms-core/main
        │
        ▼
[CI: unit + integration smoke op fixture-app]
        │ green
        ▼
Nick tagt v1.2.3 ──► Packagist webhook ──► versie publiek
        │
        ▼
Dependabot ziet bump in klant-repo's (weekly)
        │
        ▼
Opent PR in rww-website + qpmarketing-website
        │
        ▼
Ardo reviewt + merget naar main (Nick backstop bij afwezigheid)
        │
        ▼
[CI klant: composer install + lint + deploy via SSH met atomic swap]
        │
        ▼
Server: nieuwe release in releases/<sha>/, symlink-swap naar current
        │ data/ en images/uploads/ zijn symlinks naar shared/ — nooit overschreven
        ▼
Eerste request na deploy → lazy migration runt indien nodig → site live
        │
        ▼
[Smoke test: GTM nog in HTML, sitemap.xml 200, homepage 200]
```

## Vijf design-keuzes (vastgelegd)

| # | Keuze | Reden |
|---|---|---|
| 1 | **Topologie**: standalone klant-repos, `composer require easeo/cms-core` via publieke Packagist | Minimale afhankelijkheid Nick↔Ardo; Ardo ziet alleen z'n eigen repo |
| 2 | **State**: `data/*.json` volledig untracked, alleen `site.template.json` in git | Eén mentaal model voor Ardo, onmogelijk om live state te overschrijven |
| 3 | **Update-flow**: caret-pin (`^1.0`) + Dependabot PR's wekelijks | Klant houdt opt-in controle, semver beschermt tegen breaking changes, updates blijven niet liggen |
| 4 | **Schema-changes**: lazy migrations bij eerste request, file-locked, idempotent | Werkt op Hostinger shared (geen daemon), no-op voor Ardo |
| 5 | **cms-core release-gate**: full CI met unit + integration smoke op fixture-app, dan pas tag | Dependabot trusten als releases écht stabiel zijn |

## Sectie 1 — Klant-site skelet

Een klant-repo (rww-website, qpmarketing-website) bevat na cutover alleen wat site-eigen is:

```
rww-website/
├── composer.json              # require easeo/cms-core: ^1.0
├── composer.lock              # in git, reproducible builds
├── public/
│   ├── index.php              # 3-regel boot: require autoload, call Easeo\Cms\App::run()
│   ├── .htaccess              # routing rewrite naar index.php
│   └── assets/                # site-eigen statische bestanden (CSS-overrides, JS, fonts)
├── templates/                 # site-eigen template-overrides
├── images/                    # site-eigen logo's, hero-foto's (statisch)
├── site.template.json         # default config-structuur (GTM leeg, branding leeg)
├── data/
│   └── .gitkeep              # dir bestaat in git, content niet
├── .gitignore                 # data/*.json, images/uploads/, vendor/, .env
├── .github/
│   ├── workflows/deploy.yml   # CI: composer install + lint + ssh deploy
│   ├── workflows/pr-check.yml # CI op PR: composer install + lint + dry-run
│   ├── dependabot.yml         # composer updates, weekly, alleen easeo/*
│   └── PULL_REQUEST_TEMPLATE.md
├── bin/
│   └── easeo-doctor           # 1-regel wrapper: exec vendor/bin/easeo-doctor "$@" (discoverability)
├── docs/
│   ├── DEVELOPER.md           # 5-min intro voor nieuwe devs
│   ├── DEPLOY.md              # hoe deploy + rollback + troubleshooting
│   └── adr/                   # Architecture Decision Records
├── CHANGELOG.md
└── README.md                  # top-level overview + links naar docs/
```

`public/index.php` is letterlijk:
```php
<?php
require __DIR__ . '/../vendor/autoload.php';
\Easeo\Cms\App::boot(__DIR__ . '/..')->run();
```

**Template-resolution in cms-core**: `<site-root>/templates/{name}.php` → `vendor/easeo/cms-core/templates/{name}.php`. Site-overrides winnen, fallback naar core. Geen merge-conflicts mogelijk want klant raakt core nooit aan.

## Sectie 2 — State-scheiding & bootstrap

**Tracked in klant-repo** (mag overschrijven bij deploy):
- `site.template.json` — default config (lege GTM, lege branding, modules-lijst, beheer-routes)
- `templates/*.php` — site-eigen overrides
- `images/*` (geen `images/uploads/`) — statische assets
- `composer.lock`

**Gitignored** (per-server state, NOOIT in git):
```gitignore
# Klant-state — komt uit het CMS, mag nooit overschreven worden
data/*.json
data/audit.log*
data/submissions/
data/rate_limits/
data/login_attempts.json
data/.schema-version
data/.migration.lock

# Uploaded media
images/uploads/
images/thumbs/

# Build artifacts
vendor/

# Secrets
.env
```

**Bootstrap** (eerste deploy): cms-core levert `bin/easeo-bootstrap`. Idempotent — detecteert: `data/site.json` bestaat niet → kopieert `site.template.json` naar `data/site.json`, schrijft `data/.schema-version` met huidige cms-core versie, maakt lege skelet-files voor `pages.json`, `posts.json`, `navigation.json`, `users.json`, etc. Bij tweede run niets doen.

**Subsequent deploys**: `composer install --no-dev` zet `vendor/` neer. `data/` staat al gevuld door eerdere CMS-bewerkingen en wordt nooit aangeraakt (sectie 5). Apache `.htaccess` in `data/` doet `Deny from all` als dubbele beveiliging tegen directe web-toegang.

**Backup-strategie**: cms-core levert `bin/easeo-backup` dat `data/` + `images/uploads/` zipt naar `~/backups/{site}/{timestamp}.zip`. Eenmalige daily cron op de server per site (jij stelt dit in tijdens server-prep). Retentie 30 dagen.

## Sectie 3 — cms-core release-pipeline

`easeo-nl/easeo-cms` krijgt drie CI-workflows.

**`.github/workflows/ci.yml`** — bij elke push/PR naar `main`:
```yaml
jobs:
  test:
    strategy:
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - composer install
      - composer test          # PHPUnit alle packages
      - php -l elke .php in packages/
  smoke:
    needs: test
    steps:
      - composer install in apps/_fixture-app
      - php -S localhost:8080 -t apps/_fixture-app/public &
      - curl --fail http://localhost:8080/                    # homepage
      - curl --fail http://localhost:8080/blog                # blog index
      - curl --fail http://localhost:8080/blog/test-post      # blog post
      - curl --fail http://localhost:8080/contact             # contact form
      - curl --fail http://localhost:8080/sitemap.xml
      - curl --fail http://localhost:8080/feed.xml
      - login als fixture-admin → POST /beheer/site → GET / → check GTM-id rendert in HTML
```

De fixture-app (`apps/_fixture-app/`) is een minimale site met testdata, alleen voor CI. Niet in Packagist.

**`.github/workflows/release.yml`** — bij push van `v*` tag:
```yaml
jobs:
  validate-tag:
    - check dat tag op main staat
    - check dat CI groen is op die commit
    - check dat CHANGELOG.md een entry heeft voor deze versie
  split-and-publish:
    needs: validate-tag
    - splitsh/lite action: split packages/cms-core/ → easeo-nl/cms-core mirror-repo
    - tag mirror-repo met dezelfde versie
    - curl Packagist webhook (PACKAGIST_API_TOKEN secret)
```

**Sub-package releases**: gebruikt [`splitsh/lite`](https://github.com/splitsh/lite) GitHub Action om bij elke tag `packages/cms-core/` te splitsen naar een read-only `easeo-nl/cms-core` mirror-repo. Packagist trekt daarvandaan. Eenmalige setup; onzichtbaar daarna.

**Branche-protectie op `main`**: vereist CI-groen + minstens jouw approve (self-approve toegestaan voor solo). Block direct-push naar main; alles via PR zodat CI altijd draait.

## Sectie 4 — Dependabot-config op klant-repo

`.github/dependabot.yml`:
```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
      day: "monday"
      time: "08:00"
      timezone: "Europe/Amsterdam"
    allow:
      - dependency-name: "easeo/*"
    reviewers:
      - "<ardo-github-handle>"
      - "<nick-github-handle>"  # backstop bij afwezigheid Ardo
    commit-message:
      prefix: "deps"
    pull-request-branch-name:
      separator: "-"
    open-pull-requests-limit: 3
    labels:
      - "dependencies"
      - "easeo-cms"
```

Dependabot opent automatisch een PR met body inclusief release notes link en changelog link. Een aanvullende GitHub Action (`.github/workflows/dependabot-comment.yml`) reageert op elke `easeo/*` Dependabot PR met een **review-checklist comment** specifiek voor cms-core:

```markdown
## Review-checklist easeo/cms-core bump

- [ ] Release notes gelezen: <link>
- [ ] CHANGELOG entry vermeldt eventuele schema impact?
- [ ] CI op deze PR is groen
- [ ] Is dit een minor of patch? (caret-pin blokkeert majors automatisch)
- [ ] Bij twijfel: pin @nick-aldewereld

Merge = auto-deploy naar productie. Smoke-test post-deploy verifieert GTM-script en homepage 200.
```

**Voor majors** (`v2.0.0`): caret `^1.0` blokkeert deze. Dependabot opent géén PR. Nick maakt handmatig een migratie-PR met de versie-bump + eventueel pre-upgrade-checks.

**Changelog-discipline op cms-core kant**, template per release:
```markdown
## [1.3.0] - 2026-06-15
### Added
- Foo-feature in pages.json (nieuw optioneel veld `pages[].meta.canonical`)
### Schema impact
- Geen migration nodig (lazy default voor canonical = current URL)
### Action voor site-beheerders
- Geen — werkt direct na merge.
```

## Sectie 5 — Klant-deploy pipeline

`.github/workflows/deploy.yml` — opvolger van de huidige deploy.yml, veiliger:

```yaml
name: Deploy
on:
  push:
    branches: [main]
  workflow_dispatch:
concurrency:
  group: deploy-production
  cancel-in-progress: false

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer:v2
      - run: composer validate --strict
      - run: composer install --no-dev --optimize-autoloader --no-progress
      - run: composer check-platform-reqs
      - run: php -l public/index.php
      - run: tar -czf release.tgz --exclude='./data/*' --exclude='./images/uploads' --exclude='./.git' .
      - uses: actions/upload-artifact@v4
        with: {name: release, path: release.tgz}

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - uses: actions/download-artifact@v4
        with: {name: release}
      - name: SSH key setup
        run: |
          mkdir -p ~/.ssh
          printf '%s\n' "${{ secrets.DEPLOY_SSH_KEY }}" | tr -d '\r' > ~/.ssh/id_ed25519
          chmod 600 ~/.ssh/id_ed25519
          printf '%s\n' "${{ secrets.DEPLOY_KNOWN_HOSTS }}" > ~/.ssh/known_hosts
      - name: Upload + atomic swap
        env:
          DEPLOY_HOST: ${{ secrets.DEPLOY_HOST }}
          DEPLOY_USER: ${{ secrets.DEPLOY_USER }}
          DEPLOY_PORT: ${{ secrets.DEPLOY_PORT }}
          DEPLOY_PATH: ${{ secrets.DEPLOY_PATH }}
        run: |
          scp -P "$DEPLOY_PORT" release.tgz "$DEPLOY_USER@$DEPLOY_HOST:~/releases/${{ github.sha }}.tgz"
          ssh -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_HOST" \
            DEPLOY_PATH="$DEPLOY_PATH" SHA="${{ github.sha }}" bash -s <<'REMOTE'
          set -euo pipefail
          REL_DIR="$DEPLOY_PATH/../releases/$SHA"
          mkdir -p "$REL_DIR"
          tar -xzf ~/releases/$SHA.tgz -C "$REL_DIR"
          # Symlinks van shared/ in nieuwe release
          ln -sfn "$DEPLOY_PATH/../shared/data" "$REL_DIR/data"
          ln -sfn "$DEPLOY_PATH/../shared/images/uploads" "$REL_DIR/images/uploads"
          # Atomic swap via temp symlink + mv -T
          ln -sfn "$REL_DIR" "$DEPLOY_PATH-new"
          mv -Tf "$DEPLOY_PATH-new" "$DEPLOY_PATH"
          # Cleanup: bewaar laatste 5 releases
          ls -1dt "$DEPLOY_PATH/../releases/"*/ | tail -n +6 | xargs -r rm -rf
          REMOTE

  smoke:
    needs: deploy
    runs-on: ubuntu-latest
    steps:
      - run: curl -fsS https://www.<site>.nl/ -o /dev/null
      - run: curl -fsS https://www.<site>.nl/sitemap.xml -o /dev/null
      - name: GTM-check
        run: |
          curl -fsS https://www.<site>.nl/ | grep -q 'googletagmanager.com/gtm.js' \
            || { echo "::error::GTM script niet meer aanwezig in homepage HTML"; exit 1; }
```

**Drie veiligheidsmechanismen** tegen het oorspronkelijke probleem:

1. **Tarball-exclusie**: `data/` en `images/uploads/` zitten fysiek niet in `release.tgz`. Onmogelijk om te uploaden, laat staan te overschrijven.
2. **Atomic symlink-swap** (Capistrano-stijl): elke release in `releases/<sha>/`, `current` is een symlink. `data/` en `images/uploads/` zijn symlinks naar `shared/`. Failed deploy laat oude versie ongemoeid. Rollback = symlink terug op vorige sha.
3. **Smoke-test post-deploy** verifieert expliciet dat GTM-script nog in de HTML zit. Mist GTM → CI faalt → email-notificatie naar Ardo + Nick binnen minuten.

**Eenmalige server-prep per klant** (jij doet dit, ~15 min):
```
~/domains/<site>.nl/
├── public_html → releases/<sha>/public      # symlink, Apache DocumentRoot wijst hierheen
├── releases/
│   ├── abc123/
│   └── def456/
├── shared/
│   ├── data/                                 # ALLE klant-state, gemount via symlink
│   └── images/uploads/
└── ~/backups/...
```

Apache `DocumentRoot` wijst naar `public_html/` (na cutover een symlink). Hostinger shared ondersteunt symlinks en `mv -T`.

## Sectie 6 — Lazy migrations

In `cms-core` komt een nieuwe namespace `Easeo\Cms\Migration`:

```
packages/cms-core/migrations/
├── 0001_initial_skeleton.php
├── 0002_add_navigation_meta.php
├── 0003_split_legal_pages.php
└── ...
```

Migratie-format:
```php
return new class implements \Easeo\Cms\Migration\MigrationInterface {
    public function version(): int { return 2; }
    public function description(): string { return 'Add meta.canonical to pages'; }
    public function up(\Easeo\Cms\Content\ContentRepository $repo): void {
        $pages = $repo->load('pages');
        foreach ($pages as &$p) {
            $p['meta']['canonical'] ??= '';
        }
        $repo->save('pages', $pages);
    }
};
```

**Runner in `Easeo\Cms\App::boot()`** — runt bij elke web-request, idempotent dankzij version-check + file-lock:
```php
private function runPendingMigrations(): void {
    $current = $this->getSchemaVersion();   // leest data/.schema-version
    $pending = array_filter($this->scanMigrations(), fn($m) => $m->version() > $current);
    if (empty($pending)) return;

    $lock = fopen($this->siteRoot . '/data/.migration.lock', 'w');
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        return; // andere request runt al — die finished voor onze response
    }
    foreach ($pending as $m) {
        $m->up($this->contentRepo);
        $this->setSchemaVersion($m->version());
        $this->log("Migrated to v{$m->version()}: {$m->description()}");
    }
    flock($lock, LOCK_UN);
    fclose($lock);
}
```

**Eigenschappen:**
- Idempotent (version-check + file-lock)
- Geen daemon nodig — runt tijdens eerste request na deploy, ~<100ms
- Forward-only — geen `down()`. Rollback = release symlink terug + data restore uit backup als data-vorm wijzigde
- Failure-handling: exception → 500-error op die request, file-lock vrij bij proces-einde, volgende request probeert opnieuw
- Audit: elke succesvolle migration logt in `data/audit.log`

**Major-version migrations** (`^1.0` → `^2.0`) kunnen breaking changes bevatten waar lazy-migration niet veilig genoeg voor is. Cms-core levert dan ook `bin/easeo-pre-upgrade-check` om data-state te valideren vóór de upgrade. Changelog vermeldt expliciet: "v2.0 vereist handmatige pre-upgrade run + backup".

## Sectie 7 — Migratie-pad voor bestaande sites

Volgorde: jouw site eerst, daarna klant-sites in oplopende complexiteit.

**Week 1**: `apps/easeo-website` (Plan 04 monorepo-split). Als die werkt is core production-tested.
**Week 2**: `rww-website` — eenvoudiger doel (Ardo kent huidige Actions-flow al).
**Week 3**: `qpmarketing-website` — afwijkende `qp-beheer/` directory en geen Actions; eerst Actions-setup + naming-normalisatie naar `beheer/` consistent met cms-core.

**Per klant — voorbereiding** (eenmalig):

1. Tag huidige state in klant-repo: `git tag pre-cutover-snapshot && git push --tags` (rollback-anker)
2. SSH naar Hostinger, backup van live state:
   ```
   cd ~/domains/<site>/public_html
   tar -czf ~/backups/pre-cutover-data-$(date +%F).tgz data/ images/uploads/
   ```
3. Server-restructure naar Capistrano-layout (~15 min):
   ```
   cd ~/domains/<site>
   mkdir -p shared/data shared/images/uploads releases
   mv public_html/data/* shared/data/
   mv public_html/images/uploads/* shared/images/uploads/
   mv public_html releases/legacy-$(date +%F)
   ln -sfn releases/legacy-$(date +%F) public_html
   ```
4. Apache DocumentRoot wijzigen — voor cutover blijft het `public_html/`, na cutover via symlink naar `public_html/public/` binnen de release.

**Per klant — code-migratie**:

5. Nieuwe branch `cutover-to-composer` in klant-repo:
   ```
   git checkout -b cutover-to-composer
   git rm -r includes/ beheer/ lang/ <core templates>
   git rm router.php pagina-router.php <andere core-routers>
   cp -r easeo-cms/apps/_skeleton/{composer.json,public/,site.template.json,.github/,bin/,docs/} .
   composer require easeo/cms-core: ^1.0
   # Verplaats site-eigen overrides naar templates/ + assets naar public/assets/
   ```
6. Lokaal testen: `composer install && php -S localhost:8080 -t public/`. Verifieer dat alle live-pagina's renderen met een kopie van de live `data/` (rsync vanaf server).
7. PR openen op klant-repo, jij reviewt, merge naar main.
8. CI deploy doet cutover automatisch: tarball met nieuwe structuur, `releases/<new-sha>/`, symlink-swap, eerste request runt initial migrations (no-op want data is up-to-date), site live op nieuwe stack.
9. 24-48u observeren. Rollback = symlink terug op `releases/legacy-*`. Daarna legacy release verwijderen.

## Sectie 8 — Operational concerns

**Secrets-management** (per klant):
- `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT`, `DEPLOY_PATH` in GitHub Secrets per klant-repo
- **Eén deploy-key per repo** (least privilege). Comment: `github-actions-deploy@<klant>.nl`. Voorkomt dat één gecompromitteerde key alle klant-sites raakt
- `.env` op server bevat `SMTP_PASSWORD`, eventueel API-keys (Mollie etc.) — nooit in git, geüpload via aparte one-time SSH-stap

**Rollback:**
- **Code-rollback**: SSH naar server, `ln -sfn releases/<vorige-sha> current`. ~10 sec downtime tijdens symlink-swap
- **Data-rollback** (zeldzaam, alleen na falende migration die data corrupt): unpack `~/backups/<timestamp>.tgz` naar `shared/data/`, redirect een request, migration runt opnieuw (of niet, als version al matched)
- **Composer-rollback** (klant-side): revert van Dependabot-merge PR → CI deployt automatisch de oude vendor/

**Monitoring:**
- Smoke-test-faal in CI → GitHub Actions email naar Ardo + Nick
- UptimeRobot of betterstack op homepage + `/sitemap.xml` per klant-site (5-min interval)
- `/healthcheck.php` endpoint in cms-core dat versie + schema-version + writable check op `data/` returnt
- Optioneel: Sentry-PHP-SDK in cms-core voor uncaught exceptions, DSN per klant in `.env`

## Sectie 8b — Kennisoverdracht in klant-repo (slim & creatief)

Vier mechanismen werken samen om Ardo en toekomstige jr-devs zelfstandig te maken zonder dat docs stale roesten.

### (a) `bin/easeo-doctor` — diagnostic CLI

Cms-core ships een doctor-script via composer's `bin` declaratie (terechtkomend in `vendor/bin/easeo-doctor`). Elke klant-repo heeft een convenience-wrapper `bin/easeo-doctor` (`exec vendor/bin/easeo-doctor "$@"`) zodat jr-devs het script vinden door simpelweg in `bin/` te kijken. Ardo runt het lokaal of via SSH tegen de live server:

```
$ bin/easeo-doctor
EASEO CMS Doctor — rww-website
================================
✅ cms-core version: 1.2.3 (3 weken oud, laatste release: 1.5.0)
⚠️  1 openstaande Dependabot PR: #42 (bump easeo/cms-core 1.2.3 → 1.5.0)
✅ Laatste deploy: 2026-05-20 14:32 (3 dagen geleden, sha abc123)
✅ Site health: HTTP 200, GTM script aanwezig, 87ms response time
✅ Schema version: 12 (matched cms-core 1.2.3)
✅ Backup: 6u geleden, 4.2 MB
✅ Disk usage shared/: 18 MB / 1 GB quota

Volgende acties:
- Review en merge PR #42: https://github.com/easeo-nl/rww-website/pull/42
```

Output is doelgericht: vertelt wat er aan de hand is én wat te doen. Voorkomt dat Ardo moet rondklikken in GitHub + Hostinger + composer om de status te begrijpen.

### (b) `bin/easeo-explain <topic>` — concept-uitleg in de CLI

Cms-core levert een CLI die concepten on-the-spot uitlegt:

```
$ bin/easeo-explain deploys
Hoe deploys werken in deze setup
================================
Push naar main → GitHub Actions runt CI → tarball naar server → atomic symlink-swap.

Sleutel-concepten:
- "Atomic swap": de actieve site is een symlink. Switchen is één commando, no downtime.
- "Shared dir": data/ en images/uploads/ zijn symlinks naar ~/shared/. Deploys raken ze nooit.
- "Smoke test": na deploy curlt CI de homepage en verifieert dat GTM nog werkt.

Meer lezen: docs/DEPLOY.md
Bron-code: .github/workflows/deploy.yml
```

Topics: `deploys`, `migrations`, `backups`, `dependabot`, `state`, `rollback`. Eén centrale locatie in cms-core, dus consistent over alle klant-sites. Markdown-bestanden in `packages/cms-core/explain/`, de CLI cat'ed ze met ANSI-formatting.

### (c) PR-templates die door reviews leiden

`.github/PULL_REQUEST_TEMPLATE.md` in klant-repo's stelt de juiste vragen vóór merge. Voor Dependabot-PR's voegt `dependabot-comment.yml` (sectie 4) een specifieke easeo/cms-core checklist toe. Voor feature-PR's:

```markdown
## Wat verandert er?

<korte beschrijving>

## Checklist
- [ ] Lokaal getest (`php -S localhost:8080 -t public/`)
- [ ] CI groen op deze PR
- [ ] Geen `data/*.json` per ongeluk gestaged (`git status` check)
- [ ] Bij CSS/template change: verifieer in browser dat homepage nog goed rendert
- [ ] Bij twijfel: @nick-aldewereld

## Smoke-test post-merge
CI runt automatisch deze checks na deploy:
- Homepage HTTP 200
- GTM script aanwezig in HTML
- Sitemap.xml accessible
```

### (d) Architecture Decision Records (ADRs)

`docs/adr/` per klant-repo bevat korte (1-2 pagina) records voor afwijkingen van defaults. Format à la Michael Nygard, voorbeelden:

- `0001-thin-site-app-pattern.md` — waarom klant-repo geen core-code bevat
- `0002-untracked-state-files.md` — waarom data/*.json niet in git
- `0003-symlink-based-deploys.md` — waarom Capistrano-stijl met shared/
- `0004-lazy-migrations.md` — waarom migrations bij first-request, niet in CI-step

ADRs zijn append-only en gedateerd; ze leggen de **waarom** vast voor toekomstige devs die "moet ik dit niet anders doen?" denken. Stale-risico is laag want ze beschrijven de keuzes ten tijde van schrijven (geen current-state docs).

### (e) `docs/DEVELOPER.md` — 5-min onboarding

Eén bestand per klant-repo dat een nieuwe dev in 5 minuten meeneemt: wat is een thin site-app, wat zit in deze repo vs. wat in cms-core, hoe komen updates binnen, waar staat de state, hoe deploy ik, hoe rol ik terug, wie bel ik bij problemen. Link naar cms-core README voor wie dieper wil.

**Stale-resistance:** docs in klant-repo's beschrijven primair **patterns** (waarom symlinks, waarom Dependabot, waarom untracked state) niet **current state** (welke versie, welk pad). Current state komt uit `bin/easeo-doctor`. Patterns roesten niet want het ontwerp wijzigt zelden.

## Sectie 9 — Risico's & open vragen

**Risico's**

1. **Lazy migration faalt halverwege** (disk-full tijdens write) → data half-gemigreerd. Mitigatie: elke migration gebruikt atomic file-writes (`tmp` + `rename`), schema-version pas verhogen na succesvolle save
2. **PHP OPcache + symlink-deploy** — OPcache cached files op resolved path. Mitigatie: `opcache_reset()` aan begin van `App::boot()` bij detectie van versie-mismatch, of `opcache.validate_timestamps=1` (Hostinger default)
3. **Dependabot opent geen PR's** bij major-bumps → stale klant-sites. Mitigatie: maandelijkse jouw-check via `bin/easeo-check-client-versions` dat Packagist-stats vergelijkt met klant-composer.lock files
4. **Packagist downtime** tijdens deploy → `composer install` faalt → deploy faalt. Mitigatie: `composer.lock` in git zorgt voor reproducibility; Packagist-mirror config als fallback
5. **Klant logt in CMS in tijdens deploy** → race condition op `shared/data/site.json`. Niet kritiek (file-locks in cms-core), maar mogelijke UI-glitch. Mitigatie: optionele `data/.maintenance` flag, in `deploy.yml` toggelen voor/na swap (~2 sec downtime trade-off)
6. **Externe devs (Ardo) muteren templates verkeerd** en breken homepage. Mitigatie: smoke-test post-deploy is laatste vangnet. Eventueel GitHub branch-protection op klant-repo's met required CI-check

**Open vragen — beslist tijdens brainstorm 2026-05-23:**

1. Packagist publiek: ✅ cms-core publiek. Shop-module-adapters mogelijk privé in latere iteratie via Private Packagist
2. Apache vs Nginx: ✅ Apache blijft, alle deploy-templates target Apache + `.htaccess`
3. Deploy-keys: ✅ één per klant-repo (least privilege)
4. `apps/qpmarketing-website` als referentie in monorepo: ✅ ja, genesis-strategie — eerst alles ergens beginnen, daarna optimaliseren in volgende iteraties
5. Dependabot reviewer-backstop: ✅ Nick als backstop. Kennisoverdracht via mechanismen in sectie 8b

## Implementatie-fasering

Dit ontwerp is een single coherent systeem maar wordt in fasen uitgerold:

| Fase | Scope | Afhankelijk van |
|---|---|---|
| **A** | cms-core CI-pipeline (sectie 3) + fixture-app + Packagist setup | Plan 01-04 monorepo-split |
| **B** | Klant-site skelet (sectie 1) als `apps/_skeleton/` in monorepo | Fase A |
| **C** | Bootstrap script + lazy migrations runtime (sectie 2, 6) in cms-core | Fase A |
| **D** | `apps/easeo-website` migratie naar nieuwe pattern (Plan 04 monorepo-split) | Fase B, C |
| **E** | Eigen server-prep + Capistrano-layout op easeo.nl (sectie 5, 7) | Fase D |
| **F** | `rww-website` migratie inclusief deploy-pipeline, Dependabot, docs (secties 4, 5, 7, 8b) | Fase E succesvol 1 week |
| **G** | `qpmarketing-website` migratie + `apps/qpmarketing-website` referentie-app | Fase F succesvol 1 week |
| **H** | Monitoring (UptimeRobot, healthcheck endpoint), `bin/easeo-doctor`, `bin/easeo-explain`, ADR-skelet | Fase G |

Per fase een eigen implementatie-plan (writing-plans skill).
