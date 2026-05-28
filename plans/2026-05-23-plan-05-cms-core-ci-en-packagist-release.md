# Plan 05 — cms-core CI-pipeline + Packagist release (Fase A)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Maak het mogelijk om `easeo/cms-core` als versioned composer-pakket vanuit deze monorepo te releasen op publieke Packagist, met een full CI-gate (unit + integration smoke op een fixture-app) die elke release valideert vóór tag.

**Architectuur:**
- Een **fixture-app** (`apps/_fixture-app/`) is een minimale site-app met test-data die in CI gestart wordt om end-to-end rendering + admin-flows te verifiëren.
- Twee GitHub Actions workflows: `ci.yml` (PHPUnit + smoke bij elke push/PR) en `release.yml` (bij tag: validate → splitsh/lite naar mirror-repo → Packagist webhook).
- Een **read-only mirror-repo** `easeo-nl/cms-core` ontvangt subtree-splits van `packages/cms-core/` per tag. Packagist trekt daarvandaan.
- Branch-protection op `main` vereist groene CI vóór merge.

**Tech Stack:** PHP 8.1+/8.2/8.3 matrix, PHPUnit 10.5 (al aanwezig), `splitsh/lite` GitHub Action, GitHub Actions, Packagist publieke registry, bash voor diagnostiek-scripts.

**Afhankelijkheden:** Plans 01 + 02 + 03 afgerond (cms-core package-skelet bestaat, engines namespaced, modules-systeem werkt). Plan 04 (deploy-cutover) wordt later supersedet door Plan 08 — voor Fase A is alleen `packages/cms-core/` als testbaar artefact nodig.

**Afbakening:** Plan 05 produceert geen klant-deploys en geen Packagist-publieke v1.0.0. Wel een `v0.1.0-rc1` test-release die de hele pipeline valideert. Echte `v1.0.0` komt na Plan 08 (eerste productie-cutover van easeo-website op nieuwe stack).

---

## Bestandsstructuur

**Aangemaakt:**
- `apps/_fixture-app/composer.json` — minimal site-app, path-repo naar `packages/*`
- `apps/_fixture-app/public/index.php` — boot fixture-app
- `apps/_fixture-app/public/.htaccess` — Apache rewrite (gebruikt door PHP built-in server fallback ook OK)
- `apps/_fixture-app/site.template.json` — default site-config voor fixture
- `apps/_fixture-app/data/` — gitignored runtime data dir met `.gitkeep`
- `apps/_fixture-app/fixtures/` — test-data die door CI naar `data/` gekopieerd wordt vóór smoke-test
  - `fixtures/site.json` — GTM-id `GTM-FIXTURE`, branding "Fixture Site"
  - `fixtures/pages.json` — 2 pagina's (home, contact)
  - `fixtures/posts.json` — 1 blog post (slug `test-post`)
  - `fixtures/navigation.json` — basic menu
  - `fixtures/users.json` — admin met bcrypt-hash van `fixture-admin-pw`
- `apps/_fixture-app/bin/seed.sh` — kopieert `fixtures/*` → `data/`, idempotent
- `apps/_fixture-app/README.md` — "wat is deze app, hoe gebruik je 'm lokaal"
- `apps/_fixture-app/.gitignore` — `data/*.json`, `vendor/`, `*.log`
- `.github/workflows/ci.yml` — push/PR trigger: unit tests + smoke
- `.github/workflows/release.yml` — tag trigger: validate → split → Packagist
- `tools/check-changelog.php` — valideert dat CHANGELOG.md een entry heeft voor de geleverde tag
- `tools/tests/CheckChangelogTest.php` — PHPUnit-test voor het script
- `packages/cms-core/CHANGELOG.md` — Keep-a-Changelog format, eerste entry `[0.1.0-rc1]`
- `docs/packagist-setup.md` — runbook voor eenmalige Packagist + mirror-repo setup (handmatige stappen, niet automatiseerbaar)
- `docs/branch-protection.md` — eenmalige GitHub branch-protection setup
- `apps/_fixture-app/templates/` — fixture-specifieke template overrides (leeg-by-default, gebruikt cms-core defaults)

**Gewijzigd:**
- `composer.json` (root) — add `apps/_fixture-app` test-script
- `README.md` — CI-badge bovenaan
- `.gitignore` — add `apps/*/data/*.json`, `apps/*/vendor/`

---

## Engine-classificatie (vooraf)

N.v.t. voor dit plan — Plan 05 raakt geen engine-code, alleen tooling en CI.

---

### Task A1: Fixture-app skelet + composer.json

**Files:**
- Create: `apps/_fixture-app/composer.json`
- Create: `apps/_fixture-app/.gitignore`
- Create: `apps/_fixture-app/README.md`

- [ ] **Step 1: Schrijf composer.json**

```json
{
  "name": "easeo/fixture-app",
  "description": "CI-only fixture site-app voor end-to-end smoke tests van cms-core",
  "type": "project",
  "license": "MIT",
  "require": {
    "easeo/cms-core": "*"
  },
  "repositories": [
    {"type": "path", "url": "../../packages/*", "options": {"symlink": true}}
  ],
  "config": {
    "sort-packages": true
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

- [ ] **Step 2: Schrijf .gitignore**

```gitignore
# Fixture-app runtime — content komt uit fixtures/ via bin/seed.sh
data/*.json
data/audit.log*
data/submissions/
data/rate_limits/
data/login_attempts.json
data/.schema-version
data/.migration.lock
images/uploads/
images/thumbs/
vendor/
*.log
```

- [ ] **Step 3: Schrijf README.md**

```markdown
# Fixture-app — CI-only

Dit is **geen** productie-site. Het is een minimale site-app die in CI gebruikt wordt om end-to-end smoke tests te draaien op cms-core: rendert de homepage? Werkt het beheer-paneel? Komt GTM in de HTML?

## Lokaal draaien (debugging)

```
cd apps/_fixture-app
composer install
./bin/seed.sh
php -S localhost:8080 -t public
```

Open http://localhost:8080/ — admin login: `fixture-admin` / `fixture-admin-pw`.

## Hoe gebruikt CI deze app?

Zie `.github/workflows/ci.yml`, job `smoke`. CI runt `composer install`, `./bin/seed.sh`, start PHP built-in server, en curlt alle smoke-endpoints.
```

- [ ] **Step 4: Lokaal valideer composer.json**

Run: `cd apps/_fixture-app && composer validate --strict --no-check-publish`
Expected: `./composer.json is valid` (waarschuwing over ontbrekend `apps/_fixture-app/vendor/` is normaal — geen `composer install` gedraaid yet)

- [ ] **Step 5: Commit**

```bash
git add apps/_fixture-app/composer.json apps/_fixture-app/.gitignore apps/_fixture-app/README.md
git commit -m "Plan 05 A1: fixture-app skelet (composer.json + readme + gitignore)"
```

---

### Task A2: Fixture-app entry-files + templates dir

**Files:**
- Create: `apps/_fixture-app/public/index.php`
- Create: `apps/_fixture-app/public/.htaccess`
- Create: `apps/_fixture-app/site.template.json`
- Create: `apps/_fixture-app/templates/.gitkeep`
- Create: `apps/_fixture-app/data/.gitkeep`

- [ ] **Step 1: Schrijf public/index.php**

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\Easeo\Cms\App::boot(__DIR__ . '/..')->run();
```

- [ ] **Step 2: Schrijf public/.htaccess**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]

# Block direct toegang tot dotfiles
RedirectMatch 404 /\..*$
```

- [ ] **Step 3: Schrijf site.template.json**

```json
{
  "site_name": "Fixture Site",
  "site_url": "http://localhost:8080",
  "gtm_id": "GTM-FIXTURE",
  "language": "nl",
  "branding": {
    "primary_color": "#0066cc",
    "logo": ""
  },
  "modules": [],
  "smtp": {
    "host": "",
    "port": 587,
    "user": "",
    "from_email": "fixture@example.com"
  }
}
```

- [ ] **Step 4: Lege dirs met .gitkeep**

```bash
echo "" > apps/_fixture-app/templates/.gitkeep
echo "" > apps/_fixture-app/data/.gitkeep
```

- [ ] **Step 5: Verifieer dat App::boot bestaat in cms-core**

Run: `grep -rn "class App" packages/cms-core/src/ | head -3`
Expected: een match die `class App` toont in `packages/cms-core/src/App.php` of vergelijkbaar.

Als de class NOG NIET bestaat (Plan 01-03 leverde 'm niet op): dit is een prerequisite-gap. Stop en open een issue: "Plan 05 A2: cms-core ontbeert App::boot() entry point — Plan 01-03 reviewen".

- [ ] **Step 6: Lokale install + boot test**

Run:
```bash
cd apps/_fixture-app
composer install
php -d display_errors=1 public/index.php
```

Expected: ofwel HTML-output (homepage rendert leeg want geen data) of een controlled error die ergens uit de cms-core code komt (bv. "No site.json found, run bin/seed.sh" — gepland in Task C uit Plan 07 maar nu nog niet aanwezig). Geen "Class App not found" — dat zou betekenen dat Plan 01-03 incompleet is.

- [ ] **Step 7: Commit**

```bash
git add apps/_fixture-app/public/ apps/_fixture-app/site.template.json apps/_fixture-app/templates/.gitkeep apps/_fixture-app/data/.gitkeep
git commit -m "Plan 05 A2: fixture-app entry-files + template skelet"
```

---

### Task A3: Fixture test-data + seed script

**Files:**
- Create: `apps/_fixture-app/fixtures/site.json`
- Create: `apps/_fixture-app/fixtures/pages.json`
- Create: `apps/_fixture-app/fixtures/posts.json`
- Create: `apps/_fixture-app/fixtures/navigation.json`
- Create: `apps/_fixture-app/fixtures/users.json`
- Create: `apps/_fixture-app/bin/seed.sh`

- [ ] **Step 1: Schrijf fixtures/site.json**

```json
{
  "site_name": "Fixture Site",
  "site_url": "http://localhost:8080",
  "gtm_id": "GTM-FIXTURE",
  "language": "nl",
  "branding": {
    "primary_color": "#0066cc",
    "logo": ""
  },
  "modules": [],
  "smtp": {
    "host": "localhost",
    "port": 1025,
    "user": "",
    "from_email": "fixture@example.com"
  }
}
```

- [ ] **Step 2: Schrijf fixtures/pages.json**

```json
[
  {
    "slug": "",
    "title": "Welkom op de fixture",
    "content": "<p>Dit is de fixture-homepage. Als je dit ziet werkt rendering.</p>",
    "meta": {"description": "Fixture homepage"}
  },
  {
    "slug": "contact",
    "title": "Contact",
    "content": "<p>Fixture contact pagina met form.</p>",
    "meta": {"description": "Fixture contact"}
  }
]
```

- [ ] **Step 3: Schrijf fixtures/posts.json**

```json
[
  {
    "slug": "test-post",
    "title": "Test blog post",
    "date": "2026-05-23",
    "author": "Fixture Admin",
    "excerpt": "Een test-post voor smoke testing.",
    "content": "<p>Dit is de body van de test-post.</p>",
    "tags": ["test"]
  }
]
```

- [ ] **Step 4: Schrijf fixtures/navigation.json**

```json
[
  {"label": "Home", "url": "/"},
  {"label": "Blog", "url": "/blog"},
  {"label": "Contact", "url": "/contact"}
]
```

- [ ] **Step 5: Genereer bcrypt-hash voor admin password**

Run:
```bash
php -r 'echo password_hash("fixture-admin-pw", PASSWORD_BCRYPT);'
```
Expected: een 60-char string startend met `$2y$10$...`. Kopieer die output, gebruik in volgende stap.

- [ ] **Step 6: Schrijf fixtures/users.json (vervang HASH-PLACEHOLDER met de output van Step 5)**

```json
[
  {
    "username": "fixture-admin",
    "password_hash": "HASH-PLACEHOLDER",
    "role": "admin",
    "email": "admin@example.com"
  }
]
```

- [ ] **Step 7: Schrijf bin/seed.sh**

```bash
#!/usr/bin/env bash
# Kopieert fixtures/ naar data/ voor lokaal draaien of CI smoke test.
# Idempotent: overschrijft altijd, want fixture is canonical source.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

cp "$APP_DIR/fixtures/"*.json "$APP_DIR/data/"
echo "0" > "$APP_DIR/data/.schema-version"

echo "Seeded $APP_DIR/data/ from $APP_DIR/fixtures/"
ls -la "$APP_DIR/data/"
```

```bash
chmod +x apps/_fixture-app/bin/seed.sh
```

- [ ] **Step 8: Run seed.sh + verify**

Run: `./apps/_fixture-app/bin/seed.sh`
Expected: bericht "Seeded ... ls toont site.json, pages.json, posts.json, navigation.json, users.json, .schema-version".

- [ ] **Step 9: Lokale browser-test**

Run:
```bash
cd apps/_fixture-app
php -S localhost:8080 -t public &
SERVER_PID=$!
sleep 1
curl -sS http://localhost:8080/ | head -20
kill $SERVER_PID
```
Expected: HTML met "Welkom op de fixture" of vergelijkbare homepage-content. Als 500-error: cms-core boot is incompleet — fix in cms-core voor doorgaan.

- [ ] **Step 10: Commit**

```bash
git add apps/_fixture-app/fixtures/ apps/_fixture-app/bin/seed.sh
git commit -m "Plan 05 A3: fixture test-data + seed script"
```

---

### Task A4: PHPUnit-test voor seed.sh

**Files:**
- Create: `tools/tests/SeedScriptTest.php`
- Modify: `tools/phpunit.xml` (add nieuwe test-folder ALS nog niet covered door wildcard)

- [ ] **Step 1: Schrijf failing test**

```php
<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use PHPUnit\Framework\TestCase;

final class SeedScriptTest extends TestCase
{
    public function test_seed_script_copies_all_fixtures_to_data(): void
    {
        $root = dirname(__DIR__, 2);
        $appDir = "$root/apps/_fixture-app";
        $dataDir = "$appDir/data";

        // Clean
        array_map('unlink', glob("$dataDir/*.json") ?: []);
        @unlink("$dataDir/.schema-version");

        // Run
        $output = [];
        $status = 0;
        exec("$appDir/bin/seed.sh 2>&1", $output, $status);

        $this->assertSame(0, $status, "seed.sh failed: " . implode("\n", $output));
        $this->assertFileExists("$dataDir/site.json");
        $this->assertFileExists("$dataDir/pages.json");
        $this->assertFileExists("$dataDir/posts.json");
        $this->assertFileExists("$dataDir/navigation.json");
        $this->assertFileExists("$dataDir/users.json");
        $this->assertFileExists("$dataDir/.schema-version");
        $this->assertSame('0', trim(file_get_contents("$dataDir/.schema-version")));
    }

    public function test_seed_script_is_idempotent(): void
    {
        $root = dirname(__DIR__, 2);
        $appDir = "$root/apps/_fixture-app";

        // Run twice
        exec("$appDir/bin/seed.sh 2>&1");
        $firstMtime = filemtime("$appDir/data/site.json");
        sleep(1);
        $status = 0;
        exec("$appDir/bin/seed.sh 2>&1", $_, $status);

        $this->assertSame(0, $status);
        // Idempotent in effect: re-runnable, geen errors. mtime mag wijzigen (cp overschrijft) — dat is OK.
        $this->assertFileExists("$appDir/data/site.json");
    }
}
```

- [ ] **Step 2: Run test om failure te zien**

Run: `vendor/bin/phpunit tools/tests/SeedScriptTest.php`
Expected: PASS (seed.sh bestaat al, idempotency klopt).

Als test FAILS: lees de exception, fix seed.sh of test. Reden voor TDD: deze test wordt straks in CI gedraaid; we moeten zeker weten dat hij stabiel is.

- [ ] **Step 3: Commit**

```bash
git add tools/tests/SeedScriptTest.php
git commit -m "Plan 05 A4: PHPUnit test voor fixture seed.sh"
```

---

### Task A5: CI workflow — unit tests (basis)

**Files:**
- Create: `.github/workflows/ci.yml`

- [ ] **Step 1: Schrijf eerste versie van ci.yml (alleen unit tests)**

```yaml
name: CI

on:
  push:
    branches: [main, monorepo-split]
  pull_request:
    branches: [main]

concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  test:
    name: PHPUnit (PHP ${{ matrix.php }})
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.2', '8.3']
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          tools: composer:v2
          coverage: none

      - name: Cache composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ matrix.php }}-${{ hashFiles('composer.lock') }}
          restore-keys: composer-${{ matrix.php }}-

      - name: Install dependencies (root)
        run: composer install --no-progress --prefer-dist

      - name: Lint all PHP files in packages/
        run: |
          find packages -name '*.php' -print0 \
            | xargs -0 -n1 -P4 php -l \
            > /dev/null

      - name: Run PHPUnit (alle packages + tools)
        run: composer test

      - name: Validate composer.json (root)
        run: composer validate --strict
```

- [ ] **Step 2: Push naar branch + verify Actions runt**

```bash
git add .github/workflows/ci.yml
git commit -m "Plan 05 A5: CI workflow — PHPUnit matrix 8.1/8.2/8.3"
git push origin monorepo-split
```

Open https://github.com/easeo-nl/easeo-cms/actions — verifieer dat workflow start en alle 3 matrix-jobs runnen.

- [ ] **Step 3: Fix tot groen**

Verwachte initiële issues:
- PHP 8.1 mist mogelijk een feature die elders in code gebruikt is → upgrade minimum naar 8.2 in `packages/cms-core/composer.json` of fix code
- Cache mist eerste run — normaal, tweede run sneller

Iteratie: fix, commit, push, herhaal tot alle 3 matrix-jobs groen zijn. Geen smoke-step nog — die komt in A6.

- [ ] **Step 4: Commit final fixes (indien nodig)**

```bash
# als er fixes nodig waren
git add <files>
git commit -m "Plan 05 A5: fix CI tot groen op PHP 8.1/8.2/8.3"
git push
```

---

### Task A6: CI workflow — integration smoke

**Files:**
- Modify: `.github/workflows/ci.yml` (add `smoke` job)

- [ ] **Step 1: Voeg smoke-job toe (na bestaande `test`-job in ci.yml)**

```yaml
  smoke:
    name: Integration smoke (fixture-app)
    runs-on: ubuntu-latest
    needs: test
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP 8.2
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer:v2
          extensions: mbstring, json, curl, gd
          coverage: none

      - name: Install root + fixture deps
        run: |
          composer install --no-progress --prefer-dist
          (cd apps/_fixture-app && composer install --no-progress --prefer-dist)

      - name: Seed fixture data
        run: ./apps/_fixture-app/bin/seed.sh

      - name: Start PHP built-in server
        run: |
          (cd apps/_fixture-app && php -S 127.0.0.1:8080 -t public > ../../php-server.log 2>&1) &
          echo $! > /tmp/server.pid
          for i in {1..20}; do
            curl -sS -o /dev/null -w "%{http_code}" http://127.0.0.1:8080/ | grep -q "200\|302" && break
            sleep 0.5
          done
          curl -sS -o /dev/null -w "Final: %{http_code}\n" http://127.0.0.1:8080/

      - name: Smoke — homepage rendert + GTM aanwezig
        run: |
          BODY=$(curl -fsS http://127.0.0.1:8080/)
          echo "$BODY" | grep -q "Welkom op de fixture" \
            || { echo "::error::Homepage content mist"; echo "$BODY" | head -50; exit 1; }
          echo "$BODY" | grep -q "GTM-FIXTURE" \
            || { echo "::error::GTM-FIXTURE ID niet in homepage HTML"; echo "$BODY" | head -50; exit 1; }

      - name: Smoke — blog index + blog post
        run: |
          curl -fsS http://127.0.0.1:8080/blog | grep -q "Test blog post"
          curl -fsS http://127.0.0.1:8080/blog/test-post | grep -q "body van de test-post"

      - name: Smoke — sitemap + feed + contact
        run: |
          curl -fsS http://127.0.0.1:8080/sitemap.xml | grep -q "<urlset"
          curl -fsS http://127.0.0.1:8080/feed.xml | grep -q "<rss"
          curl -fsS http://127.0.0.1:8080/contact | grep -q "Fixture contact"

      - name: Smoke — admin login + change site_name
        run: |
          # 1. login → cookie
          curl -c /tmp/cookies -fsS -X POST \
            -d "username=fixture-admin&password=fixture-admin-pw" \
            http://127.0.0.1:8080/beheer/login

          # 2. update site via beheer
          curl -b /tmp/cookies -fsS -X POST \
            -d "site_name=Smoke-test-name&gtm_id=GTM-FIXTURE" \
            http://127.0.0.1:8080/beheer/site

          # 3. homepage moet nieuwe naam tonen
          curl -fsS http://127.0.0.1:8080/ | grep -q "Smoke-test-name" \
            || { echo "::error::Beheer-update niet zichtbaar op homepage"; exit 1; }

      - name: Stop server + show log on failure
        if: always()
        run: |
          kill $(cat /tmp/server.pid) 2>/dev/null || true
          if [ -f php-server.log ]; then
            echo "::group::PHP server log"
            cat php-server.log
            echo "::endgroup::"
          fi
```

- [ ] **Step 2: Commit + push**

```bash
git add .github/workflows/ci.yml
git commit -m "Plan 05 A6: CI smoke — integration test op fixture-app"
git push
```

- [ ] **Step 3: Verifieer Actions groen**

Wacht tot workflow voltooid (~3-5 minuten). Verwacht: alle test-jobs + smoke groen.

Verwachte failure-modi en fixes:
- Routes niet gevonden (`/blog`, `/contact`): controleer dat cms-core routing-component die routes default registreert
- GTM niet in HTML: controleer dat `Easeo\Cms\Branding\BrandConfig::render()` of vergelijkbaar de GTM-snippet uitrolt
- Admin login faalt: controleer `users.json`-format vs. `Easeo\Cms\Auth`-implementatie

Iteratie: voor elk failure-type een fix, commit, push, herhaal tot smoke groen.

- [ ] **Step 4: Commit fixes (indien nodig)**

```bash
git add <files>
git commit -m "Plan 05 A6: fixes voor smoke groen (route X / GTM-render / auth flow)"
git push
```

---

### Task A7: CHANGELOG + check-changelog tool

**Files:**
- Create: `packages/cms-core/CHANGELOG.md`
- Create: `tools/check-changelog.php`
- Create: `tools/tests/CheckChangelogTest.php`

- [ ] **Step 1: Schrijf CHANGELOG.md**

```markdown
# Changelog — easeo/cms-core

Alle wijzigingen aan `easeo/cms-core` staan in dit bestand.

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
Versionering: [SemVer](https://semver.org/lang/nl/).

## [Unreleased]

## [0.1.0-rc1] - 2026-05-23

### Added
- Eerste release-candidate van cms-core na monorepo-split (Plans 01-04)
- CI-pipeline met PHPUnit matrix (PHP 8.1/8.2/8.3) + integration smoke op fixture-app
- Packagist-publicatie via splitsh/lite mirror-repo

### Schema impact
- N.v.t. — initial release

### Action voor site-beheerders
- N.v.t. — geen klant-installaties yet (eerste productie-cutover komt in Plan 08)
```

- [ ] **Step 2: Schrijf failing test voor check-changelog**

```php
<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use PHPUnit\Framework\TestCase;

final class CheckChangelogTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'changelog');
    }

    protected function tearDown(): void
    {
        @unlink($this->tmpFile);
    }

    public function test_passes_when_changelog_has_entry_for_version(): void
    {
        file_put_contents($this->tmpFile, "# Changelog\n\n## [1.2.3] - 2026-05-23\n\n### Added\n- Foo\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile 1.2.3 2>&1", $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
    }

    public function test_fails_when_changelog_has_no_entry_for_version(): void
    {
        file_put_contents($this->tmpFile, "# Changelog\n\n## [1.2.3] - 2026-05-23\n\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile 9.9.9 2>&1", $output, $status);
        $this->assertNotSame(0, $status);
        $this->assertStringContainsString('9.9.9', implode("\n", $output));
    }

    public function test_strips_v_prefix_from_tag(): void
    {
        file_put_contents($this->tmpFile, "## [1.2.3] - 2026-05-23\n### Added\n- x\n");
        $output = [];
        $status = 0;
        exec("php " . dirname(__DIR__) . "/check-changelog.php $this->tmpFile v1.2.3 2>&1", $output, $status);
        $this->assertSame(0, $status);
    }
}
```

- [ ] **Step 3: Run test — verifieer failure (script bestaat nog niet)**

Run: `vendor/bin/phpunit tools/tests/CheckChangelogTest.php`
Expected: FAIL — "Failed to open ... check-changelog.php" of vergelijkbaar.

- [ ] **Step 4: Schrijf tools/check-changelog.php**

```php
<?php
declare(strict_types=1);

// Usage: php check-changelog.php <changelog-file> <version>
// Exits 0 if changelog has a `## [<version>]` heading, non-zero otherwise.

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php check-changelog.php <changelog-file> <version>\n");
    exit(2);
}

[$_, $changelogPath, $version] = $argv;

if (!is_readable($changelogPath)) {
    fwrite(STDERR, "Cannot read $changelogPath\n");
    exit(3);
}

// Strip leading 'v' if present (so tags v1.2.3 → 1.2.3)
$version = ltrim($version, 'v');
$content = file_get_contents($changelogPath);

if (preg_match('/^##\s*\[' . preg_quote($version, '/') . '\]/m', $content) !== 1) {
    fwrite(STDERR, "ERROR: no '## [$version]' entry found in $changelogPath\n");
    fwrite(STDERR, "Add a section before tagging:\n\n## [$version] - YYYY-MM-DD\n\n### Added/Changed/Fixed\n- ...\n");
    exit(1);
}

echo "OK: changelog has entry for $version\n";
exit(0);
```

- [ ] **Step 5: Run test — verifieer passes**

Run: `vendor/bin/phpunit tools/tests/CheckChangelogTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Manueel test op echte CHANGELOG**

Run:
```bash
php tools/check-changelog.php packages/cms-core/CHANGELOG.md v0.1.0-rc1
```
Expected: `OK: changelog has entry for 0.1.0-rc1`, exit 0.

Run:
```bash
php tools/check-changelog.php packages/cms-core/CHANGELOG.md v9.9.9
echo $?
```
Expected: error message, exit 1.

- [ ] **Step 7: Commit**

```bash
git add packages/cms-core/CHANGELOG.md tools/check-changelog.php tools/tests/CheckChangelogTest.php
git commit -m "Plan 05 A7: CHANGELOG + check-changelog tool met PHPUnit tests"
git push
```

---

### Task A8: Packagist + mirror-repo setup (handmatig, runbook)

Deze taak is **handmatig** (GitHub-org admin + Packagist account vereist). Het runbook documenteert de stappen zodat ze reproduceerbaar zijn voor toekomstige packages (shop-module, hello-module).

**Files:**
- Create: `docs/packagist-setup.md`

- [ ] **Step 1: Schrijf docs/packagist-setup.md**

```markdown
# Packagist + mirror-repo setup voor easeo/cms-core

> Eenmalig per package. Geldt nu voor `cms-core`; herhaal voor `shop-module` en `hello-module` als die later publiek gaan.

## Stap 1: Maak mirror-repo aan

Mirror-repo's zijn read-only — alleen splitsh/lite pushed ernaar. Naam-conventie: `easeo-nl/<package-naam-zonder-vendor>`.

```bash
gh repo create easeo-nl/cms-core --public \
  --description "READ-ONLY mirror van easeo-nl/easeo-cms packages/cms-core/. Issues + PRs in de monorepo." \
  --homepage "https://github.com/easeo-nl/easeo-cms"
```

In GitHub UI:
- Settings → General → Default branch: `main`
- Settings → Branches → Branch protection rule voor `main`: alleen `nick-aldewereld` mag pushen (write zonder PR vereist voor splitsh-action), maar regelarchitectuur: **niemand handmatig committen**

## Stap 2: README op mirror-repo

Push een minimale README zodat duidelijk is dat deze repo niet bedoeld is voor PRs:

```bash
cd /tmp && git clone git@github.com:easeo-nl/cms-core.git
cd cms-core
cat > README.md <<'EOF'
# easeo/cms-core (mirror)

Dit is een **read-only mirror** van [`easeo-nl/easeo-cms`](https://github.com/easeo-nl/easeo-cms) — specifiek de directory `packages/cms-core/`. Deze repo is alleen het distributie-kanaal voor Composer/Packagist.

## Issues + Pull Requests

Open ze in de monorepo: https://github.com/easeo-nl/easeo-cms/issues

## Installatie

```bash
composer require easeo/cms-core
```

Zie de monorepo README voor documentatie.
EOF
git add README.md
git commit -m "Initial mirror README"
git push origin main
cd .. && rm -rf cms-core
```

## Stap 3: Packagist-account + submit

1. Account: https://packagist.org/ → log in met `easeo-nl` GitHub org (of persoonlijk account met org-toegang)
2. Submit: https://packagist.org/packages/submit → paste `https://github.com/easeo-nl/cms-core`
3. Verifieer dat package-naam `easeo/cms-core` is (uit composer.json van packages/cms-core/)
4. Maintainers: voeg `nick-aldewereld` toe als maintainer

## Stap 4: Auto-update webhook

Packagist haalt updates uit GitHub:

1. Op Packagist: My Packages → `easeo/cms-core` → Settings → kopieer de **API token** en jouw username
2. Op de mirror-repo GitHub: Settings → Webhooks → Add webhook:
   - Payload URL: `https://packagist.org/api/github?username=<jouw-packagist-username>`
   - Content type: `application/json`
   - Secret: `<packagist-api-token>`
   - Events: alleen `push`
3. Test: push een lege commit naar de mirror → Packagist → My Packages → "Last update" updates binnen 30s.

## Stap 5: PACKAGIST_API_TOKEN secret in monorepo

Voor release.yml om handmatig Packagist te triggeren (fallback als webhook faalt):

```bash
gh secret set PACKAGIST_API_TOKEN -R easeo-nl/easeo-cms < <(echo "<token>")
gh secret set PACKAGIST_USERNAME -R easeo-nl/easeo-cms < <(echo "<username>")
```

## Verificatie

- https://packagist.org/packages/easeo/cms-core toont package met versies
- `composer search easeo/cms-core` (in een willekeurige composer-project) vindt 'm
- `composer require easeo/cms-core:^0.1` werkt in een test-dir
```

- [ ] **Step 2: Voer de handmatige stappen uit**

Volg de runbook stap voor stap. Niet skippen; latere tasks (A9, A11) hangen af van werkende Packagist-submission.

- [ ] **Step 3: Commit het runbook**

```bash
git add docs/packagist-setup.md
git commit -m "Plan 05 A8: Packagist + mirror-repo setup runbook"
git push
```

---

### Task A9: Release workflow — tag → splitsh → Packagist

**Files:**
- Create: `.github/workflows/release.yml`

- [ ] **Step 1: Schrijf release.yml**

```yaml
name: Release

on:
  push:
    tags:
      - 'v*.*.*'
      - 'v*.*.*-*'   # rc / beta / alpha

jobs:
  validate:
    name: Validate tag
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Verify tag is on main
        run: |
          if ! git merge-base --is-ancestor $GITHUB_SHA origin/main; then
            echo "::error::Tag $GITHUB_REF_NAME is not on main branch"
            exit 1
          fi

      - name: Verify CHANGELOG entry exists
        run: |
          php tools/check-changelog.php packages/cms-core/CHANGELOG.md "$GITHUB_REF_NAME"

  split-cms-core:
    name: Split + publish easeo/cms-core
    needs: validate
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0   # splitsh heeft volledige history nodig

      - name: Setup splitsh/lite
        run: |
          curl -fsSL https://github.com/splitsh/lite/releases/download/v2.0.0/lite_linux_amd64.tar.gz \
            | tar xz -C /tmp
          sudo mv /tmp/splitsh-lite /usr/local/bin/

      - name: Split packages/cms-core to mirror-repo
        env:
          MIRROR_DEPLOY_KEY: ${{ secrets.MIRROR_CMS_CORE_DEPLOY_KEY }}
        run: |
          # Setup SSH key for mirror push
          mkdir -p ~/.ssh
          printf '%s\n' "$MIRROR_DEPLOY_KEY" | tr -d '\r' > ~/.ssh/id_ed25519
          chmod 600 ~/.ssh/id_ed25519
          ssh-keyscan github.com >> ~/.ssh/known_hosts 2>/dev/null

          # Split de subtree → krijg sha terug van split-commit
          SPLIT_SHA=$(splitsh-lite --prefix=packages/cms-core)
          echo "Split commit: $SPLIT_SHA"

          # Push naar mirror
          git remote add mirror git@github.com:easeo-nl/cms-core.git
          git push mirror "$SPLIT_SHA:refs/heads/main" --force
          git push mirror "$SPLIT_SHA:refs/tags/$GITHUB_REF_NAME"

      - name: Trigger Packagist update
        env:
          PACKAGIST_USERNAME: ${{ secrets.PACKAGIST_USERNAME }}
          PACKAGIST_API_TOKEN: ${{ secrets.PACKAGIST_API_TOKEN }}
        run: |
          curl -fsS -XPOST -H 'content-type:application/json' \
            "https://packagist.org/api/update-package?username=${PACKAGIST_USERNAME}&apiToken=${PACKAGIST_API_TOKEN}" \
            -d '{"repository":{"url":"https://github.com/easeo-nl/cms-core"}}'

  release-notes:
    name: Generate GitHub release notes
    needs: split-cms-core
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Extract CHANGELOG entry voor deze tag
        id: changelog
        run: |
          VERSION="${GITHUB_REF_NAME#v}"
          # Extract van "## [$VERSION]" tot volgende "## [" of EOF
          NOTES=$(awk "/^## \[$VERSION\]/{found=1; next} /^## \[/{found=0} found" \
            packages/cms-core/CHANGELOG.md)
          {
            echo "notes<<EOF"
            echo "$NOTES"
            echo "EOF"
          } >> "$GITHUB_OUTPUT"

      - name: Create GitHub release
        uses: softprops/action-gh-release@v2
        with:
          body: ${{ steps.changelog.outputs.notes }}
          prerelease: ${{ contains(github.ref_name, '-') }}
```

- [ ] **Step 2: Setup MIRROR_CMS_CORE_DEPLOY_KEY secret**

```bash
# Op je lokale machine:
ssh-keygen -t ed25519 -C "splitsh-deploy@cms-core" -f /tmp/mirror_cms_core -N ""

# Pubkey naar mirror-repo als deploy key MET write-toegang:
gh repo deploy-key add /tmp/mirror_cms_core.pub \
  -R easeo-nl/cms-core \
  -t "splitsh deploy key" \
  -w

# Private key als secret in monorepo:
gh secret set MIRROR_CMS_CORE_DEPLOY_KEY \
  -R easeo-nl/easeo-cms \
  < /tmp/mirror_cms_core

rm /tmp/mirror_cms_core /tmp/mirror_cms_core.pub
```

- [ ] **Step 3: Commit + push**

```bash
git add .github/workflows/release.yml
git commit -m "Plan 05 A9: release workflow — splitsh → mirror → Packagist"
git push
```

---

### Task A10: Eerste test-release v0.1.0-rc1 (end-to-end pipeline test)

- [ ] **Step 1: Verifieer dat alle voorgaande tasks groen zijn**

```bash
# Lokale checks
composer test
./apps/_fixture-app/bin/seed.sh

# Remote checks
gh run list -R easeo-nl/easeo-cms --workflow=ci.yml --limit 3
```
Expected: laatste CI-run op de branch is groen op alle PHP-versies + smoke.

- [ ] **Step 2: Merge branch naar main (als nog op monorepo-split)**

```bash
gh pr create --base main --head monorepo-split \
  --title "Plan 05: CI + Packagist release pipeline" \
  --body "Implements Plan 05 (Fase A). See specs/2026-05-23-easeo-cms-cicd-en-update-flow-design.md"
# Review eigen PR, merge
gh pr merge --merge
git checkout main && git pull
```

- [ ] **Step 3: Tag v0.1.0-rc1**

```bash
git tag -a v0.1.0-rc1 -m "First release candidate after monorepo split — Plan 05 validation"
git push origin v0.1.0-rc1
```

- [ ] **Step 4: Watch release workflow**

```bash
gh run watch -R easeo-nl/easeo-cms
```
Expected: 3 jobs (`validate`, `split-cms-core`, `release-notes`) allemaal groen.

- [ ] **Step 5: Verifieer mirror-repo**

```bash
gh release list -R easeo-nl/cms-core
git ls-remote --tags git@github.com:easeo-nl/cms-core.git
```
Expected: `v0.1.0-rc1` tag aanwezig op mirror.

- [ ] **Step 6: Verifieer Packagist**

Open https://packagist.org/packages/easeo/cms-core. Verifieer:
- Versie `0.1.0-rc1` zichtbaar
- "Last update" recent (< 1 min na tag-push)

- [ ] **Step 7: Verifieer composer require werkt**

```bash
mkdir -p /tmp/cms-core-install-test && cd /tmp/cms-core-install-test
composer require easeo/cms-core:^0.1.0-rc1 --no-scripts
ls vendor/easeo/cms-core/src/
cd / && rm -rf /tmp/cms-core-install-test
```
Expected: install succeeds, `vendor/easeo/cms-core/src/` bevat de bekende namespace-struct.

- [ ] **Step 8: Failure-mode test — broken CHANGELOG**

Test dat de release-gate werkt:
```bash
git tag -a v9.9.9 -m "Broken: no changelog entry"
git push origin v9.9.9
gh run watch -R easeo-nl/easeo-cms
```
Expected: `validate` job faalt met "no '## [9.9.9]' entry found".

Cleanup:
```bash
git tag -d v9.9.9
git push --delete origin v9.9.9
```

- [ ] **Step 9: Commit release-test log (geen code-change, maar markeer in CHANGELOG)**

```markdown
# In packages/cms-core/CHANGELOG.md, update [Unreleased] sectie:

## [Unreleased]
### Validated
- Plan 05 end-to-end pipeline test: v0.1.0-rc1 successfully published to Packagist via splitsh/lite mirror, composer require works
```

```bash
git add packages/cms-core/CHANGELOG.md
git commit -m "Plan 05 A10: release-pipeline e2e validated met v0.1.0-rc1"
git push
```

---

### Task A11: Branch-protection op main + docs

**Files:**
- Create: `docs/branch-protection.md`

- [ ] **Step 1: Schrijf docs/branch-protection.md**

```markdown
# Branch protection — easeo-nl/easeo-cms

Eenmalige setup op `main`. Voorkomt dat CI-skipping releases bereiken.

## Settings

GitHub UI → Settings → Branches → Add branch protection rule:

- **Branch name pattern:** `main`
- **Require a pull request before merging:** ON
  - Required approvals: 1 (jij keurt je eigen PR goed via tweede acc of skip-if-solo via `gh pr merge --auto`)
  - Dismiss stale approvals when new commits are pushed: ON
- **Require status checks to pass before merging:** ON
  - Required checks: `PHPUnit (PHP 8.1)`, `PHPUnit (PHP 8.2)`, `PHPUnit (PHP 8.3)`, `Integration smoke (fixture-app)`
  - Require branches to be up to date before merging: ON
- **Require conversation resolution before merging:** ON
- **Do not allow bypassing the above settings:** ON (ook voor admins)
- **Restrict who can push to matching branches:** alleen admins (`nick-aldewereld`)

## Tags

Tag-protection (Settings → Tags → New rule):
- Pattern: `v*`
- Restrict creation tot admin

Voorkomt dat een merge per ongeluk een tag pusht; tagging blijft handmatige actie.
```

- [ ] **Step 2: Voer setup uit via GitHub UI**

Volg het runbook. Verifieer:
```bash
gh api repos/easeo-nl/easeo-cms/branches/main/protection
```
Expected: JSON die laat zien dat required_status_checks bevat: ci.yml jobs.

- [ ] **Step 3: Test door PR te maken met failing test**

```bash
git checkout -b test-branch-protection
# Voeg een bewust falende test toe
cat > /tmp/fail.php <<'EOF'
<?php
namespace Easeo\Cms\Tests;
use PHPUnit\Framework\TestCase;
class IntentionalFailureTest extends TestCase {
    public function test_fails(): void { $this->fail("intentional"); }
}
EOF
mv /tmp/fail.php packages/cms-core/tests/IntentionalFailureTest.php
git add packages/cms-core/tests/IntentionalFailureTest.php
git commit -m "TEST: verify branch protection blocks failing PR"
git push -u origin test-branch-protection
gh pr create --base main --head test-branch-protection --title "TEST" --body "verify CI blocks"

# Probeer te mergen — moet falen
gh pr merge --merge
```
Expected: merge geblokkeerd, "Required status check expected".

Cleanup:
```bash
gh pr close test-branch-protection --delete-branch
git checkout main
git branch -D test-branch-protection
```

- [ ] **Step 4: Commit docs**

```bash
git add docs/branch-protection.md
git commit -m "Plan 05 A11: branch-protection runbook + verified blocking"
git push
```

---

### Task A12: CI-badge in README + plan-completion

**Files:**
- Modify: `README.md` (root van monorepo)

- [ ] **Step 1: Voeg CI-badge bovenaan README.md**

Toevoegen direct onder de eerste H1:

```markdown
[![CI](https://github.com/easeo-nl/easeo-cms/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/easeo-nl/easeo-cms/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/easeo/cms-core?label=easeo%2Fcms-core)](https://packagist.org/packages/easeo/cms-core)
[![License](https://img.shields.io/packagist/l/easeo/cms-core)](LICENSE.txt)
```

- [ ] **Step 2: Update CHANGELOG met plan-completion**

In `packages/cms-core/CHANGELOG.md`, voeg toe onder `[Unreleased]`:

```markdown
### Added
- CI badge in monorepo README
- Plan 05 (Fase A) compleet
```

- [ ] **Step 3: Commit + verify badges renderen**

```bash
git add README.md packages/cms-core/CHANGELOG.md
git commit -m "Plan 05 A12: CI + Packagist badges in README, plan-status"
git push
```

Open https://github.com/easeo-nl/easeo-cms — verifieer dat beide badges renderen.

---

## Plan-status

| Task | Status |
|---|---|
| A1 — fixture-app skelet | ☐ |
| A2 — entry-files + templates | ☐ |
| A3 — fixture test-data + seed.sh | ☐ |
| A4 — PHPUnit test voor seed.sh | ☐ |
| A5 — CI workflow unit tests | ☐ |
| A6 — CI workflow smoke | ☐ |
| A7 — CHANGELOG + check-changelog tool | ☐ |
| A8 — Packagist + mirror-repo setup | ☐ |
| A9 — Release workflow | ☐ |
| A10 — eerste test-release v0.1.0-rc1 | ☐ |
| A11 — branch-protection | ☐ |
| A12 — CI-badge + plan-completion | ☐ |

## Self-review notities (voor executor)

- **Fixture-app vs productie-site-app:** fixture-app is bewust minimaal en CI-only. Het is GEEN template voor klant-sites (dat komt in Plan 06 als `apps/_skeleton/`). Verwar ze niet.
- **TDD-discipline:** elke nieuwe PHP-class krijgt eerst een failing test (A4, A7). CI-workflows zijn niet unit-testbaar — daar valt de discipline op "push + observe Actions UI, iterate tot groen".
- **Geen merge naar main vóór CI groen op feature-branch.** Branch protection forceert dit (A11), maar in A1-A6 werk je nog op `monorepo-split` branch.
- **Handmatige stappen (A8, A11) zijn one-shot.** Vergeet niet de runbooks bij te werken als de UI van GitHub of Packagist verandert.
- **Volgende plan (06)** wacht tot dit plan volledig groen is. Plan 06 bouwt het klant-site skelet dat in Plan 08+ gebruikt wordt.
