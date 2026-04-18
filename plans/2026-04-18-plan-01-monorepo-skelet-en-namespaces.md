# Plan 01 — Monorepo-skelet + namespace-refactor (Fase A+B)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transformeer `easeo-cms` van één PHP-app naar een monorepo met `packages/cms-core` (Composer-library met PSR-4-namespaces) en `apps/easeo-website` (thin site-app). Eind-status: easeo.nl draait op de nieuwe structuur, alle engines zijn PSR-4-geklasseerd, legacy-bridge is weg.

**Architectuur:** Fase A verplaatst bestaande bestanden niet-destructief met een tijdelijke `legacy-bridge.php` die oude `require_once`-paden redirectt. Fase B converteert engines één-voor-één via een AST-gebaseerd rename-script (`nikic/php-parser`), waarbij zowel functie-declaraties als call-sites worden omgezet. Na laatste engine wordt de bridge verwijderd.

**Tech Stack:** PHP 8.1+, Composer, PSR-4 autoloading, PHPUnit 10, nikic/php-parser 4.x, git.

**Uitgangspunt:** repo op main branch, `git status` clean. Werk in aparte feature-branch `monorepo-split`.

---

## Bestandsstructuur

**Aangemaakt:**
- `composer.json` (root)
- `.gitignore` (nieuwe entries)
- `packages/cms-core/composer.json`
- `packages/cms-core/phpunit.xml`
- `packages/cms-core/tests/bootstrap.php`
- `apps/easeo-website/composer.json`
- `apps/easeo-website/public/index.php` (minimale bootstrap die huidige routing gebruikt)
- `tools/legacy-bridge.php`
- `tools/rename-engine.php` (AST rename-script)
- `tools/tests/RenameEngineTest.php`
- `tools/mappings/<engine>.json` (per engine, 16 stuks)
- Per engine: `packages/cms-core/src/<Subdir>/<ClassName>.php` (16 classes)
- Per engine: `packages/cms-core/tests/<Subdir>/<ClassName>Test.php` (16 tests)

**Verplaatst (via `git mv`):**
- `includes/*.php` → `packages/cms-core/src/legacy/*.php` (tijdelijk, totdat rename-script ze converteert)
- `includes/phpmailer/` → `packages/cms-core/vendor-legacy/phpmailer/` (blijft vendored — Composer kan het ook via `phpmailer/phpmailer`, wordt in aparte vervolg-task vervangen)
- `templates/` → `packages/cms-core/templates/`
- `beheer/` → `packages/cms-core/beheer/`
- `lang/` → `packages/cms-core/lang/`
- Root PHP-bestanden (`index.php`, `blog.php`, `contact.php`, `pagina.php`, `pagina-router.php`, `blog-post.php`, `sitemap.php`, `feed.php`, `form-handler.php`, `router.php`, `404.php`, `privacyverklaring.php`, `voorwaarden.php`, `cookiebeleid.php`, `over.php`, `setup.php`, `install.php`) → `apps/easeo-website/public/`
- `css/`, `images/`, `data/`, `robots.txt`, `site.template.json` → `apps/easeo-website/public/` (behalve `data/` → `apps/easeo-website/data/` buiten DocumentRoot)

**Verwijderd aan einde Fase B:**
- `tools/legacy-bridge.php`
- `packages/cms-core/src/legacy/` (leeg na laatste engine-conversie)

---

# Fase A — Monorepo-skelet (niet-destructief)

### Task A1: Feature-branch + root composer.json

**Files:**
- Create: `composer.json` (root)

- [ ] **Step 1: Nieuwe feature-branch aanmaken**

```bash
cd /mnt/nvme1tb/projects/easeo-cms
git checkout -b monorepo-split
git status
```
Expected: `On branch monorepo-split`, clean.

- [ ] **Step 2: Root `composer.json` schrijven**

Create `composer.json`:
```json
{
    "name": "easeo/easeo-cms-monorepo",
    "description": "EASEO CMS monorepo — cms-core library + site-apps",
    "type": "project",
    "license": "MIT",
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "nikic/php-parser": "^4.19"
    },
    "scripts": {
        "test": [
            "@test:core",
            "@test:tools"
        ],
        "test:core": "phpunit --configuration packages/cms-core/phpunit.xml",
        "test:tools": "phpunit --configuration tools/phpunit.xml"
    },
    "autoload-dev": {
        "psr-4": {
            "Easeo\\Tools\\": "tools/src/",
            "Easeo\\Tools\\Tests\\": "tools/tests/"
        }
    }
}
```

- [ ] **Step 3: Dependencies installeren**

```bash
composer install
```
Expected: `vendor/` aangemaakt met phpunit en nikic/php-parser.

- [ ] **Step 4: `.gitignore` uitbreiden**

Edit `.gitignore`, voeg toe (maak aan als niet bestaat):
```
# Composer
/vendor/
/apps/*/vendor/
/packages/*/vendor/
composer.lock

# Env
/apps/*/.env

# Editor
.idea/
.vscode/
*.swp

# Build / tests
.phpunit.result.cache
coverage/
```
Houd bestaande regels intact als die er zijn.

- [ ] **Step 5: Commit**

```bash
git add composer.json .gitignore
git commit -m "Fase A: root composer.json + gitignore"
```

---

### Task A2: Package-skelet `cms-core`

**Files:**
- Create: `packages/cms-core/composer.json`
- Create: `packages/cms-core/phpunit.xml`
- Create: `packages/cms-core/tests/bootstrap.php`
- Create: `packages/cms-core/tests/SmokeTest.php`

- [ ] **Step 1: Map-structuur aanmaken**

```bash
mkdir -p packages/cms-core/{src,tests,templates,beheer,lang}
```

- [ ] **Step 2: Schrijf `packages/cms-core/composer.json`**

```json
{
    "name": "easeo/cms-core",
    "description": "EASEO CMS core library — engines, templates, beheer framework, router",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=8.1"
    },
    "autoload": {
        "psr-4": {
            "Easeo\\Cms\\": "src/"
        },
        "files": [
            "src/legacy/bootstrap.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "Easeo\\Cms\\Tests\\": "tests/"
        }
    }
}
```

De `files` autoload bootstrapt `src/legacy/` tijdens Fase A — wordt verwijderd aan einde Fase B.

- [ ] **Step 3: Schrijf `packages/cms-core/phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="cms-core">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 4: Schrijf `tests/bootstrap.php`**

```php
<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
```

- [ ] **Step 5: Schrijf falende smoketest**

Create `packages/cms-core/tests/SmokeTest.php`:
```php
<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {
    public function testAutoloaderWerkt(): void {
        $this->assertTrue(class_exists('PHPUnit\\Framework\\TestCase'));
    }
}
```

- [ ] **Step 6: Run smoketest**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Expected: 1 test passed.

Dit faalt mogelijk met "src/legacy/bootstrap.php not found" — maak voor nu een lege placeholder:
```bash
mkdir -p packages/cms-core/src/legacy
echo '<?php // wordt in Fase A ingevuld' > packages/cms-core/src/legacy/bootstrap.php
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```

- [ ] **Step 7: Commit**

```bash
git add packages/cms-core/ composer.lock
git commit -m "Fase A: cms-core package-skelet + PHPUnit"
```

---

### Task A3: Engines verplaatsen (`includes/` → `packages/cms-core/src/legacy/`)

**Files:**
- Move: alle `includes/*.php` → `packages/cms-core/src/legacy/*.php`
- Move: `includes/phpmailer/` → `packages/cms-core/src/legacy/phpmailer/`

- [ ] **Step 1: Git mv**

```bash
git mv includes/audit.php            packages/cms-core/src/legacy/audit.php
git mv includes/blog-engine.php      packages/cms-core/src/legacy/blog-engine.php
git mv includes/brand.php            packages/cms-core/src/legacy/brand.php
git mv includes/content.php          packages/cms-core/src/legacy/content.php
git mv includes/cookie-consent.php   packages/cms-core/src/legacy/cookie-consent.php
git mv includes/footer.php           packages/cms-core/src/legacy/footer.php
git mv includes/form-engine.php      packages/cms-core/src/legacy/form-engine.php
git mv includes/header.php           packages/cms-core/src/legacy/header.php
git mv includes/lang.php             packages/cms-core/src/legacy/lang.php
git mv includes/legal.php            packages/cms-core/src/legacy/legal.php
git mv includes/mailer.php           packages/cms-core/src/legacy/mailer.php
git mv includes/media-engine.php     packages/cms-core/src/legacy/media-engine.php
git mv includes/navigation.php       packages/cms-core/src/legacy/navigation.php
git mv includes/rate-limiter.php     packages/cms-core/src/legacy/rate-limiter.php
git mv includes/structured-data.php  packages/cms-core/src/legacy/structured-data.php
git mv includes/tracking-body.php    packages/cms-core/src/legacy/tracking-body.php
git mv includes/tracking-head.php    packages/cms-core/src/legacy/tracking-head.php
git mv includes/phpmailer            packages/cms-core/src/legacy/phpmailer
```

- [ ] **Step 2: Verplaats templates, beheer, lang**

```bash
git mv templates/* packages/cms-core/templates/
git mv beheer/*    packages/cms-core/beheer/
git mv lang/*      packages/cms-core/lang/
rmdir templates beheer lang includes
```

- [ ] **Step 3: Vul `src/legacy/bootstrap.php`**

Overwrite `packages/cms-core/src/legacy/bootstrap.php`:
```php
<?php
/**
 * Fase A bootstrap — laadt alle legacy engines.
 * Wordt verwijderd aan einde Fase B (wanneer alle engines PSR-4 zijn).
 */
$legacyDir = __DIR__;
$engines = [
    'content.php',        // eerst — andere engines requiren dit
    'lang.php',
    'brand.php',
    'audit.php',
    'rate-limiter.php',
    'mailer.php',
    'form-engine.php',
    'blog-engine.php',
    'legal.php',
    'cookie-consent.php',
    'media-engine.php',
    'navigation.php',
    'structured-data.php',
    'tracking-head.php',
    'tracking-body.php',
    'header.php',
    'footer.php',
];
foreach ($engines as $engine) {
    $path = $legacyDir . '/' . $engine;
    if (is_file($path)) {
        require_once $path;
    }
}
```

- [ ] **Step 4: Smoketest — oude code laadt nog via bootstrap**

Create `packages/cms-core/tests/LegacyLoadTest.php`:
```php
<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;

class LegacyLoadTest extends TestCase {
    public function testLegacyFunctiesBeschikbaar(): void {
        $this->assertTrue(function_exists('brand_css_properties'), 'brand.php moet geladen zijn');
        $this->assertTrue(function_exists('send_mail'), 'mailer.php moet geladen zijn');
        $this->assertTrue(function_exists('get_forms'), 'form-engine.php moet geladen zijn');
    }
}
```

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Expected: 2 tests passed (SmokeTest + LegacyLoadTest).

Als test faalt met "undefined function" of "file not found": check `site()` en andere helpers die engines requireden — die zitten in `content.php`. Check dat `content.php` als eerste staat in engines-lijst.

- [ ] **Step 5: Commit**

```bash
git add .
git commit -m "Fase A: verplaats includes/, templates/, beheer/, lang/ naar packages/cms-core/"
```

---

### Task A4: Site-app skelet `apps/easeo-website`

**Files:**
- Create: `apps/easeo-website/composer.json`
- Create: `apps/easeo-website/public/.htaccess` (gekopieerd van root)
- Move: root entry-PHP's → `apps/easeo-website/public/`
- Move: `data/` → `apps/easeo-website/data/`

- [ ] **Step 1: Map-structuur**

```bash
mkdir -p apps/easeo-website/{public,data,templates}
```

- [ ] **Step 2: Schrijf `apps/easeo-website/composer.json`**

```json
{
    "name": "easeo/easeo-website",
    "description": "easeo.nl site-app",
    "type": "project",
    "require": {
        "php": ">=8.1",
        "easeo/cms-core": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "../../packages/*",
            "options": {"symlink": true}
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 3: Composer install in site-app**

```bash
cd apps/easeo-website
composer install
cd ../..
```
Expected: `apps/easeo-website/vendor/easeo/cms-core` is een symlink naar `../../../packages/cms-core`. Check met `ls -la apps/easeo-website/vendor/easeo/`.

- [ ] **Step 4: Verplaats root-entry PHP-bestanden**

```bash
git mv 404.php                apps/easeo-website/public/
git mv blog.php               apps/easeo-website/public/
git mv blog-post.php          apps/easeo-website/public/
git mv contact.php            apps/easeo-website/public/
git mv cookiebeleid.php       apps/easeo-website/public/
git mv feed.php               apps/easeo-website/public/
git mv form-handler.php       apps/easeo-website/public/
git mv index.php              apps/easeo-website/public/
git mv install.php            apps/easeo-website/public/
git mv over.php               apps/easeo-website/public/
git mv pagina.php             apps/easeo-website/public/
git mv pagina-router.php      apps/easeo-website/public/
git mv privacyverklaring.php  apps/easeo-website/public/
git mv router.php             apps/easeo-website/public/
git mv setup.php              apps/easeo-website/public/
git mv sitemap.php            apps/easeo-website/public/
git mv voorwaarden.php        apps/easeo-website/public/
```

- [ ] **Step 5: Verplaats css/images/robots + data**

```bash
git mv css          apps/easeo-website/public/css
git mv images       apps/easeo-website/public/images
git mv robots.txt   apps/easeo-website/public/
git mv data         apps/easeo-website/data
git mv site.template.json apps/easeo-website/
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Fase A: easeo-website site-app skelet + verplaats entry-files"
```

---

### Task A5: Legacy-bridge voor require-paden

**Files:**
- Create: `tools/legacy-bridge.php`
- Modify: `apps/easeo-website/public/index.php` + andere entries (bootstrap-include)

De oude entry-files hebben `require_once __DIR__ . '/includes/X.php'`. Die paden kloppen niet meer. De bridge vangt dat op door `__DIR__ . '/includes/'` (wat vanaf `public/` nu bestaat als niet-bestaande pad) om te mappen naar `packages/cms-core/src/legacy/`.

- [ ] **Step 1: Schrijf `tools/legacy-bridge.php`**

```php
<?php
/**
 * Legacy-bridge — maps oude `includes/X.php` require-paden naar nieuwe
 * `packages/cms-core/src/legacy/X.php` locatie tijdens Fase A.
 *
 * Geladen via Composer autoload (zie packages/cms-core/composer.json files-array).
 * Wordt verwijderd aan einde Fase B.
 */

if (!defined('EASEO_LEGACY_BRIDGE_LOADED')) {
    define('EASEO_LEGACY_BRIDGE_LOADED', true);

    // Constants die oude code verwacht
    if (!defined('EASEO_ROOT')) {
        // public/index.php staat in apps/easeo-website/public/ — root = 3 niveaus hoger
        define('EASEO_ROOT', dirname(__DIR__, 2));
    }
    if (!defined('EASEO_APP')) {
        define('EASEO_APP', EASEO_ROOT . '/apps/easeo-website');
    }
    if (!defined('EASEO_DATA')) {
        define('EASEO_DATA', EASEO_APP . '/data');
    }
    if (!defined('EASEO_CORE')) {
        define('EASEO_CORE', EASEO_ROOT . '/packages/cms-core');
    }
    if (!defined('EASEO_TEMPLATES')) {
        define('EASEO_TEMPLATES', EASEO_CORE . '/templates');
    }
    if (!defined('EASEO_LANG')) {
        define('EASEO_LANG', EASEO_CORE . '/lang');
    }
    if (!defined('EASEO_BEHEER')) {
        define('EASEO_BEHEER', EASEO_CORE . '/beheer');
    }
}
```

- [ ] **Step 2: Update `packages/cms-core/src/legacy/bootstrap.php` om bridge eerst te laden**

Prepend to existing bootstrap.php:
```php
<?php
require_once dirname(__DIR__, 4) . '/tools/legacy-bridge.php';

// ... rest van bestaande content ...
```

- [ ] **Step 3: Update entry-files — vervang `require_once __DIR__ . '/includes/...'` door Composer autoload**

Elke entry-file in `apps/easeo-website/public/` heeft bovenaan statements zoals:
```php
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/header.php';
```

Vervang al deze door één regel aan de top:
```php
require_once __DIR__ . '/../vendor/autoload.php';
```

Doe dit per file: `index.php`, `blog.php`, `contact.php`, `pagina.php`, `pagina-router.php`, `blog-post.php`, `sitemap.php`, `feed.php`, `form-handler.php`, `router.php`, `404.php`, `privacyverklaring.php`, `voorwaarden.php`, `cookiebeleid.php`, `over.php`, `setup.php`, `install.php`.

Gebruik `grep -rn "require_once.*includes" apps/easeo-website/public/` om alle instances te vinden.

- [ ] **Step 4: Smoketest — homepage laadt**

```bash
cd apps/easeo-website
php -S localhost:8000 public/router.php
```
Browse naar http://localhost:8000 in een andere terminal met curl:
```bash
curl -sI http://localhost:8000/ | head -1
```
Expected: `HTTP/1.1 200 OK`.

Als 500-error: check PHP error log, meestal een vergeten constant of path.

```bash
pkill -f "php -S localhost:8000" || true
cd ../..
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Fase A: legacy-bridge + entry-files via Composer autoload"
```

---

### Task A6: Fase A integratie-test — alle pagina's renderen

**Files:**
- Create: `packages/cms-core/tests/Integration/PageRenderingTest.php`

- [ ] **Step 1: Test schrijven die homepage + blog + contact rendert**

Create `packages/cms-core/tests/Integration/PageRenderingTest.php`:
```php
<?php
namespace Easeo\Cms\Tests\Integration;

use PHPUnit\Framework\TestCase;

class PageRenderingTest extends TestCase {
    private static $serverPid = null;
    private static $baseUrl = 'http://localhost:8765';

    public static function setUpBeforeClass(): void {
        $root = dirname(__DIR__, 4);
        $router = $root . '/apps/easeo-website/public/router.php';
        $docroot = $root . '/apps/easeo-website/public';
        $cmd = sprintf('php -S localhost:8765 -t %s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg($docroot),
            escapeshellarg($router)
        );
        self::$serverPid = (int)trim(shell_exec($cmd));
        usleep(500000); // wacht 0.5s tot server op is
    }

    public static function tearDownAfterClass(): void {
        if (self::$serverPid) {
            shell_exec('kill ' . self::$serverPid . ' 2>/dev/null');
        }
    }

    public function testHomepageReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertSame(200, $status, 'Homepage moet 200 OK zijn');
        $this->assertNotEmpty($body);
    }

    public function testBlogReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/blog/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->assertSame(200, $status);
    }

    public function testContactReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/contact/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->assertSame(200, $status);
    }

    public function testSitemapReturns200(): void {
        $ch = curl_init(self::$baseUrl . '/sitemap.xml');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->assertSame(200, $status);
        $this->assertStringContainsString('<urlset', $body);
    }
}
```

- [ ] **Step 2: Run integratie-test**

```bash
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml --testsuite cms-core
```
Expected: alle tests pass (SmokeTest, LegacyLoadTest, PageRenderingTest).

Als een pagina 500 returneert: hoogstwaarschijnlijk een hardcoded pad in een engine. Debug via `tail -f apps/easeo-website/data/logs/*.log` (als er error logs zijn) of run handmatig `php apps/easeo-website/public/index.php` en kijk naar stdout.

- [ ] **Step 3: Commit Fase A afsluiting**

```bash
git add -A
git commit -m "Fase A: integratie-tests — homepage/blog/contact/sitemap renderen"
git tag fase-a-complete
```

---

# Fase B — Namespace-refactor via AST rename-script

## Engine-classificatie (vooraf)

Tijdens spec-review is gescand welke engines procedurele functies hebben. Er zijn **drie categorieën**:

**Categorie 1 — Function-to-class conversie** (rename-script van toepassing):
| Engine | Functies | Aantal |
|---|---|---|
| `content.php` | load_json, save_json, invalidate_json_cache, site, page_content, e, is_setup_complete, handle_redirects, check_setup | 9 |
| `lang.php` | t + lang-helpers | ~4 |
| `brand.php` | brand_css_properties, brand_google_fonts_url, brand_tailwind_config | 3 |
| `mailer.php` | send_mail, encrypt_smtp_password, decrypt_smtp_password | 3 |
| `form-engine.php` | get_forms_data, get_forms, save_forms, get_form, render_form, csrf_token_frontend, verify_csrf_frontend | 7 |
| `blog-engine.php` | get_posts_data, get_posts, save_posts, get_published_posts, get_post_by_slug, get_post_by_id, create_post, update_post, delete_post, get_categories, paginate_posts, generate_slug, render_post_card | 13 |
| `audit.php` | audit_log, read_audit_log | 2 |
| `legal.php` | get_legal_text, replace_legal_placeholders, get_default_legal | 3 |
| `media-engine.php` | get_media, save_media, upload_media, delete_media, resize_image, create_thumbnail, create_image_from_file, save_image, preserve_transparency, format_file_size | 10 |
| `navigation.php` | get_dynamic_page_menu_items, merge_nav_with_dynamic, render_main_nav, render_mobile_nav, render_footer_nav | 5 |
| `structured-data.php` | get_base_url, schema_organization, schema_website, schema_breadcrumbs, schema_article, render_structured_data | 6 |

**Categorie 2 — Namespacing-only** (class bestaat al, alleen namespace toevoegen):
| Engine | Bestaande class | Target namespace |
|---|---|---|
| `rate-limiter.php` | `RateLimiter` | `Easeo\Cms\Security\RateLimiter` |

**Categorie 3 — Template files** (geen PHP-functies, alleen output + inline i18n-helpers — verplaatsen naar `packages/cms-core/templates/layout/`):
| Engine | Nieuwe locatie |
|---|---|
| `header.php` | `packages/cms-core/templates/layout/header.php` |
| `footer.php` | `packages/cms-core/templates/layout/footer.php` |
| `cookie-consent.php` | `packages/cms-core/templates/layout/cookie-consent.php` |
| `tracking-head.php` | `packages/cms-core/templates/layout/tracking-head.php` |
| `tracking-body.php` | `packages/cms-core/templates/layout/tracking-body.php` |

**Gevolg voor tasks:**
- Categorie 1 volgt het rename-script-patroon (tasks B3–B17 met dry-run/execute/test/commit).
- Categorie 2 (rate-limiter) krijgt een aparte task met alleen `namespace`-declaratie toevoegen + callers fixen — geen AST-rewrite nodig (Task B17a).
- Categorie 3 (5 template-files) worden in één task verplaatst + includes/requires updated in callers (Task B18).

### Task B1: Rename-script bouwen — AST-analyser

**Files:**
- Create: `tools/src/RenameEngine.php`
- Create: `tools/phpunit.xml`
- Create: `tools/tests/bootstrap.php`
- Create: `tools/tests/RenameEngineTest.php`
- Create: `tools/tests/fixtures/sample-engine.php`
- Create: `tools/tests/fixtures/sample-caller.php`

Het rename-script gebruikt `nikic/php-parser` om:
1. Alle `function foo_bar()` declaraties in `packages/cms-core/src/legacy/<engine>.php` te vervangen door `class ClassName { public static function camelCase(...) }`.
2. Alle call-sites (`foo_bar(...)`) in de monorepo te vervangen door `ClassName::camelCase(...)`.
3. `use ClassName` statements toe te voegen aan bestanden die de class gebruiken.
4. De bewerkte engine-file te verplaatsen naar `packages/cms-core/src/<Subdir>/<ClassName>.php`.

**Input per engine**: een mapping-JSON (`tools/mappings/<engine>.json`) die zegt: welke functies horen bij welke class en welke subdirectory.

- [ ] **Step 1: Schrijf test voor RenameEngine**

Create `tools/phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="tools">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Create `tools/tests/bootstrap.php`:
```php
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
```

Create `tools/tests/fixtures/sample-engine.php`:
```php
<?php
function sample_hello(string $name): string {
    return "Hallo, $name";
}

function sample_shout(string $text): string {
    return strtoupper($text) . '!';
}
```

Create `tools/tests/fixtures/sample-caller.php`:
```php
<?php
echo sample_hello('wereld');
echo sample_shout('hi');
```

Create `tools/tests/RenameEngineTest.php`:
```php
<?php
namespace Easeo\Tools\Tests;

use Easeo\Tools\RenameEngine;
use PHPUnit\Framework\TestCase;

class RenameEngineTest extends TestCase {
    private string $tmpDir;

    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . '/rename-test-' . uniqid();
        mkdir($this->tmpDir);
        mkdir($this->tmpDir . '/legacy');
        mkdir($this->tmpDir . '/callers');
        copy(__DIR__ . '/fixtures/sample-engine.php', $this->tmpDir . '/legacy/sample.php');
        copy(__DIR__ . '/fixtures/sample-caller.php', $this->tmpDir . '/callers/caller.php');
    }

    protected function tearDown(): void {
        shell_exec('rm -rf ' . escapeshellarg($this->tmpDir));
    }

    public function testConverteertFunctiesNaarStaticClassMethods(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => [
                'sample_hello' => 'hello',
                'sample_shout' => 'shout',
            ],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $renamer->rename($mapping, dryRun: false);

        $produced = file_get_contents($this->tmpDir . '/src/Sample/Greeter.php');
        $this->assertStringContainsString('namespace Easeo\\Cms\\Sample;', $produced);
        $this->assertStringContainsString('class Greeter', $produced);
        $this->assertStringContainsString('public static function hello(string $name): string', $produced);
        $this->assertStringContainsString('public static function shout(string $text): string', $produced);
    }

    public function testVervangtCallSitesInCallers(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => ['sample_hello' => 'hello', 'sample_shout' => 'shout'],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $renamer->rename($mapping, dryRun: false);

        $caller = file_get_contents($this->tmpDir . '/callers/caller.php');
        $this->assertStringContainsString('Greeter::hello(\'wereld\')', $caller);
        $this->assertStringContainsString('Greeter::shout(\'hi\')', $caller);
        $this->assertStringContainsString('use Easeo\\Cms\\Sample\\Greeter;', $caller);
    }

    public function testDryRunVeranderDirectoryNiet(): void {
        $mapping = [
            'engine' => 'sample',
            'subdir' => 'Sample',
            'class' => 'Greeter',
            'namespace' => 'Easeo\\Cms\\Sample',
            'functions' => ['sample_hello' => 'hello', 'sample_shout' => 'shout'],
        ];

        $renamer = new RenameEngine(
            legacyDir: $this->tmpDir . '/legacy',
            targetBase: $this->tmpDir . '/src',
            callerDirs: [$this->tmpDir . '/callers'],
        );
        $diff = $renamer->rename($mapping, dryRun: true);

        $this->assertFileDoesNotExist($this->tmpDir . '/src/Sample/Greeter.php');
        $this->assertFileExists($this->tmpDir . '/legacy/sample.php');
        $this->assertNotEmpty($diff);
    }
}
```

- [ ] **Step 2: Run test — moet falen (class bestaat nog niet)**

```bash
./vendor/bin/phpunit --configuration tools/phpunit.xml
```
Expected: Fatal error: class `Easeo\Tools\RenameEngine` not found, OR 3 tests failed.

- [ ] **Step 3: Implementeer `tools/src/RenameEngine.php`**

Create `tools/src/RenameEngine.php`:
```php
<?php
namespace Easeo\Tools;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class RenameEngine {
    public function __construct(
        private string $legacyDir,
        private string $targetBase,
        private array $callerDirs,
    ) {}

    /**
     * @return array diff-samenvatting (dry-run) of [] na succesvolle rename
     */
    public function rename(array $mapping, bool $dryRun = false): array {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $printer = new Standard();

        $engineFile = $this->legacyDir . '/' . $mapping['engine'] . '.php';
        if (!is_file($engineFile)) {
            throw new \RuntimeException("Engine-bestand niet gevonden: $engineFile");
        }

        $ast = $parser->parse(file_get_contents($engineFile));
        $newClass = $this->buildClassFromFunctions($ast, $mapping);

        // Schrijf nieuwe class-file
        $targetDir = $this->targetBase . '/' . $mapping['subdir'];
        $targetFile = $targetDir . '/' . $mapping['class'] . '.php';

        $classCode = "<?php\nnamespace {$mapping['namespace']};\n\n" . $printer->prettyPrint([$newClass]) . "\n";

        $callerChanges = $this->rewriteCallers($mapping, $parser, $printer);

        if ($dryRun) {
            return [
                'target_file' => $targetFile,
                'target_code_preview' => substr($classCode, 0, 500),
                'caller_changes' => $callerChanges,
            ];
        }

        // Echt schrijven
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        file_put_contents($targetFile, $classCode);
        unlink($engineFile);

        foreach ($callerChanges as $file => $newContent) {
            file_put_contents($file, $newContent);
        }

        return [];
    }

    private function buildClassFromFunctions(array $ast, array $mapping): Stmt\Class_ {
        $methods = [];
        foreach ($ast as $stmt) {
            if (!$stmt instanceof Stmt\Function_) continue;
            $fnName = (string)$stmt->name;
            if (!isset($mapping['functions'][$fnName])) continue;

            $methodName = $mapping['functions'][$fnName];
            $method = new Stmt\ClassMethod($methodName, [
                'flags' => Stmt\Class_::MODIFIER_PUBLIC | Stmt\Class_::MODIFIER_STATIC,
                'params' => $stmt->params,
                'returnType' => $stmt->returnType,
                'stmts' => $stmt->stmts,
            ]);
            $methods[] = $method;
        }

        return new Stmt\Class_($mapping['class'], ['stmts' => $methods]);
    }

    private function rewriteCallers(array $mapping, $parser, Standard $printer): array {
        $changes = [];
        $fullClass = $mapping['namespace'] . '\\' . $mapping['class'];

        foreach ($this->callerDirs as $dir) {
            $files = $this->findPhpFiles($dir);
            foreach ($files as $file) {
                $source = file_get_contents($file);
                $ast = $parser->parse($source);
                if ($ast === null) continue;

                $visitor = new CallSiteRewriter($mapping['functions'], $mapping['class']);
                $traverser = new NodeTraverser();
                $traverser->addVisitor($visitor);
                $newAst = $traverser->traverse($ast);

                if ($visitor->hasChanges()) {
                    $newAst = $this->ensureUseStatement($newAst, $fullClass);
                    $changes[$file] = "<?php\n" . $printer->prettyPrint(array_slice($newAst, 0)) . "\n";
                }
            }
        }

        return $changes;
    }

    private function ensureUseStatement(array $ast, string $fullClass): array {
        // Check of use-statement al bestaat
        foreach ($ast as $stmt) {
            if ($stmt instanceof Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    if ((string)$use->name === $fullClass) return $ast;
                }
            }
        }

        $useStmt = new Stmt\Use_([new Stmt\UseUse(new Name($fullClass))]);
        array_unshift($ast, $useStmt);
        return $ast;
    }

    private function findPhpFiles(string $dir): array {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $files[] = $f->getPathname();
            }
        }
        return $files;
    }
}

class CallSiteRewriter extends NodeVisitorAbstract {
    private bool $changed = false;

    public function __construct(private array $fnMap, private string $className) {}

    public function leaveNode(Node $node) {
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $fnName = (string)$node->name;
            if (isset($this->fnMap[$fnName])) {
                $this->changed = true;
                return new Node\Expr\StaticCall(
                    new Name($this->className),
                    new Identifier($this->fnMap[$fnName]),
                    $node->args
                );
            }
        }
        return null;
    }

    public function hasChanges(): bool { return $this->changed; }
}
```

- [ ] **Step 4: Run tests — moeten passen**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration tools/phpunit.xml
```
Expected: 3 tests passed.

Als een test faalt: het printer-output formaat kan licht afwijken van wat de test verwacht. Pas de assertions aan: gebruik `assertMatchesRegularExpression` voor flexibele spacing, of normaliseer whitespace vóór vergelijking.

- [ ] **Step 5: CLI-wrapper schrijven**

Create `tools/rename-engine.php`:
```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Easeo\Tools\RenameEngine;

$opts = getopt('', ['engine:', 'mapping:', 'dry-run', 'help']);

if (isset($opts['help']) || !isset($opts['engine'])) {
    echo "Gebruik: php tools/rename-engine.php --engine=<name> [--mapping=<file>] [--dry-run]\n";
    echo "Voorbeeld: php tools/rename-engine.php --engine=brand --dry-run\n";
    exit($opts['help'] ?? false ? 0 : 1);
}

$engine = $opts['engine'];
$mappingFile = $opts['mapping'] ?? __DIR__ . '/mappings/' . $engine . '.json';
$dryRun = isset($opts['dry-run']);

if (!is_file($mappingFile)) {
    fwrite(STDERR, "Mapping-file niet gevonden: $mappingFile\n");
    exit(1);
}

$mapping = json_decode(file_get_contents($mappingFile), true, flags: JSON_THROW_ON_ERROR);

$root = dirname(__DIR__);
$renamer = new RenameEngine(
    legacyDir: $root . '/packages/cms-core/src/legacy',
    targetBase: $root . '/packages/cms-core/src',
    callerDirs: [
        $root . '/apps/easeo-website/public',
        $root . '/packages/cms-core/templates',
        $root . '/packages/cms-core/beheer',
        $root . '/packages/cms-core/src/legacy',
    ],
);

$result = $renamer->rename($mapping, $dryRun);

if ($dryRun) {
    echo "DRY-RUN — geen wijzigingen geschreven\n";
    echo "Target: {$result['target_file']}\n";
    echo "Preview:\n{$result['target_code_preview']}\n";
    echo "\nCaller-changes: " . count($result['caller_changes']) . " file(s)\n";
    foreach ($result['caller_changes'] as $file => $_) {
        echo "  - $file\n";
    }
} else {
    echo "Rename voltooid voor engine '$engine'.\n";
}
```

Make executable:
```bash
chmod +x tools/rename-engine.php
```

- [ ] **Step 6: Commit**

```bash
git add tools/ composer.json composer.lock
git commit -m "Fase B: AST rename-script + tests (3 pass)"
```

---

### Task B2: Mapping-files voor alle 17 engines

**Files:**
- Create: `tools/mappings/brand.json`
- Create: `tools/mappings/lang.json`
- Create: `tools/mappings/content.json`
- Create: `tools/mappings/mailer.json`
- Create: `tools/mappings/form-engine.json`
- Create: `tools/mappings/blog-engine.json`
- Create: `tools/mappings/audit.json`
- Create: `tools/mappings/legal.json`
- Create: `tools/mappings/cookie-consent.json`
- Create: `tools/mappings/media-engine.json`
- Create: `tools/mappings/navigation.json`
- Create: `tools/mappings/rate-limiter.json`
- Create: `tools/mappings/structured-data.json`
- Create: `tools/mappings/tracking-head.json`
- Create: `tools/mappings/tracking-body.json`
- Create: `tools/mappings/header.json`
- Create: `tools/mappings/footer.json`

Elke mapping-file bevat: engine-naam, target-subdir, class-naam, namespace, functie-mapping.

- [ ] **Step 1: Scan functie-namen per engine**

Voor elke engine in `packages/cms-core/src/legacy/`:
```bash
grep -E '^function [a-z_]+' packages/cms-core/src/legacy/brand.php
```
Noteer alle `function X()` declaraties. Doe dit voor alle 17 engines.

- [ ] **Step 2: Schrijf mapping voor `brand` (voorbeeld)**

Create `tools/mappings/brand.json`:
```json
{
    "engine": "brand",
    "subdir": "Branding",
    "class": "BrandConfig",
    "namespace": "Easeo\\Cms\\Branding",
    "functions": {
        "brand_css_properties": "cssProperties",
        "brand_google_fonts_url": "googleFontsUrl",
        "brand_tailwind_config": "tailwindConfig"
    }
}
```

- [ ] **Step 3: Schrijf mapping voor `lang`**

Create `tools/mappings/lang.json`:
```json
{
    "engine": "lang",
    "subdir": "Lang",
    "class": "Translator",
    "namespace": "Easeo\\Cms\\Lang",
    "functions": {
        "t": "translate",
        "lang_current": "currentLocale",
        "lang_set": "setLocale",
        "lang_available": "availableLocales"
    }
}
```
**Let op `t()`**: dit is een zeer veelgebruikte helper. Na refactor wordt het `Translator::translate(...)`. Check na rename-execution dat alle templates/beheer-views correct zijn omgezet.

- [ ] **Step 4: Schrijf mapping voor `content`**

Create `tools/mappings/content.json`:
```json
{
    "engine": "content",
    "subdir": "Content",
    "class": "ContentRepository",
    "namespace": "Easeo\\Cms\\Content",
    "functions": {
        "load_json": "loadJson",
        "save_json": "saveJson",
        "invalidate_json_cache": "invalidateJsonCache",
        "site": "siteValue",
        "page_content": "pageContent",
        "e": "escape",
        "is_setup_complete": "isSetupComplete",
        "handle_redirects": "handleRedirects",
        "check_setup": "checkSetup"
    }
}
```
**Let op:** `e()` wordt `escape()` — heel veelgebruikt in templates. Na rename-execution grep naar overgebleven `<?= e(` calls in templates.

- [ ] **Step 5: Schrijf mapping voor `mailer`**

```json
{
    "engine": "mailer",
    "subdir": "Mail",
    "class": "Mailer",
    "namespace": "Easeo\\Cms\\Mail",
    "functions": {
        "send_mail": "send",
        "encrypt_smtp_password": "encryptSmtpPassword",
        "decrypt_smtp_password": "decryptSmtpPassword"
    }
}
```

- [ ] **Step 6: Schrijf mapping voor `form-engine`**

```json
{
    "engine": "form-engine",
    "subdir": "Form",
    "class": "FormEngine",
    "namespace": "Easeo\\Cms\\Form",
    "functions": {
        "get_forms_data": "getFormsData",
        "get_forms": "getForms",
        "save_forms": "saveForms",
        "get_form": "getForm",
        "render_form": "render",
        "csrf_token_frontend": "csrfToken",
        "verify_csrf_frontend": "verifyCsrf"
    }
}
```

- [ ] **Step 7: Schrijf mappings voor resterende Categorie-1 engines**

Alleen voor engines met procedurele functies (zie Engine-classificatie bovenaan Fase B).

Create `tools/mappings/blog-engine.json`:
```json
{
    "engine": "blog-engine",
    "subdir": "Blog",
    "class": "BlogEngine",
    "namespace": "Easeo\\Cms\\Blog",
    "functions": {
        "get_posts_data": "getPostsData",
        "get_posts": "getPosts",
        "save_posts": "savePosts",
        "get_published_posts": "getPublishedPosts",
        "get_post_by_slug": "getPostBySlug",
        "get_post_by_id": "getPostById",
        "create_post": "createPost",
        "update_post": "updatePost",
        "delete_post": "deletePost",
        "get_categories": "getCategories",
        "paginate_posts": "paginatePosts",
        "generate_slug": "generateSlug",
        "render_post_card": "renderPostCard"
    }
}
```

Create `tools/mappings/audit.json`:
```json
{
    "engine": "audit",
    "subdir": "Audit",
    "class": "AuditLogger",
    "namespace": "Easeo\\Cms\\Audit",
    "functions": {
        "audit_log": "log",
        "read_audit_log": "read"
    }
}
```

Create `tools/mappings/legal.json`:
```json
{
    "engine": "legal",
    "subdir": "Legal",
    "class": "LegalPages",
    "namespace": "Easeo\\Cms\\Legal",
    "functions": {
        "get_legal_text": "getText",
        "replace_legal_placeholders": "replacePlaceholders",
        "get_default_legal": "getDefault"
    }
}
```

Create `tools/mappings/media-engine.json`:
```json
{
    "engine": "media-engine",
    "subdir": "Media",
    "class": "MediaLibrary",
    "namespace": "Easeo\\Cms\\Media",
    "functions": {
        "get_media": "getMedia",
        "save_media": "saveMedia",
        "upload_media": "uploadMedia",
        "delete_media": "deleteMedia",
        "resize_image": "resizeImage",
        "create_thumbnail": "createThumbnail",
        "create_image_from_file": "createImageFromFile",
        "save_image": "saveImage",
        "preserve_transparency": "preserveTransparency",
        "format_file_size": "formatFileSize"
    }
}
```

Create `tools/mappings/navigation.json`:
```json
{
    "engine": "navigation",
    "subdir": "Navigation",
    "class": "Menu",
    "namespace": "Easeo\\Cms\\Navigation",
    "functions": {
        "get_dynamic_page_menu_items": "getDynamicPageMenuItems",
        "merge_nav_with_dynamic": "mergeNavWithDynamic",
        "render_main_nav": "renderMainNav",
        "render_mobile_nav": "renderMobileNav",
        "render_footer_nav": "renderFooterNav"
    }
}
```

Create `tools/mappings/structured-data.json`:
```json
{
    "engine": "structured-data",
    "subdir": "Seo",
    "class": "StructuredData",
    "namespace": "Easeo\\Cms\\Seo",
    "functions": {
        "get_base_url": "getBaseUrl",
        "schema_organization": "schemaOrganization",
        "schema_website": "schemaWebsite",
        "schema_breadcrumbs": "schemaBreadcrumbs",
        "schema_article": "schemaArticle",
        "render_structured_data": "render"
    }
}
```

- [ ] **Step 8: Commit**

```bash
git add tools/mappings/
git commit -m "Fase B: mapping-files voor alle 17 engines"
```

---

### Task B3: Engine 1 — `content` (eerst, omdat anderen er van afhangen)

**Files:**
- Gewijzigd: `packages/cms-core/src/legacy/content.php` (verwijderd)
- Create: `packages/cms-core/src/Content/ContentRepository.php`
- Create: `packages/cms-core/tests/Content/ContentRepositoryTest.php`
- Modified: alle callers van `site()`, `load_json()`, etc.

`content.php` bevat de fundamentele `site()` helper — enorm veel callers. Dit is de riskantste engine om eerst te doen, maar ook de meest prioritaire omdat andere engines ervan afhangen.

- [ ] **Step 1: Dry-run**

```bash
php tools/rename-engine.php --engine=content --dry-run
```
Expected output toont target-path + lijst van callers. Lees output aandachtig — check of alle verwachte callers worden geraakt.

- [ ] **Step 2: Test voor ContentRepository schrijven vóór rename**

Create `packages/cms-core/tests/Content/ContentRepositoryTest.php`:
```php
<?php
namespace Easeo\Cms\Tests\Content;

use Easeo\Cms\Content\ContentRepository;
use PHPUnit\Framework\TestCase;

class ContentRepositoryTest extends TestCase {
    public function testSiteValueLeestUitSiteJson(): void {
        // Setup tijdelijke data-dir
        $tmp = sys_get_temp_dir() . '/content-test-' . uniqid();
        mkdir($tmp);
        file_put_contents($tmp . '/site.json', json_encode([
            'brand' => ['color_primary' => '#FF0000'],
        ]));

        putenv('EASEO_DATA=' . $tmp);
        if (!defined('EASEO_DATA')) define('EASEO_DATA', $tmp);

        $value = ContentRepository::siteValue('brand.color_primary', '#000');
        $this->assertSame('#FF0000', $value);

        $fallback = ContentRepository::siteValue('nonexistent.key', 'fallback');
        $this->assertSame('fallback', $fallback);

        shell_exec('rm -rf ' . escapeshellarg($tmp));
    }
}
```

- [ ] **Step 3: Run test — moet falen**

```bash
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml --filter ContentRepositoryTest
```
Expected: fail met "class not found".

- [ ] **Step 4: Execute rename**

```bash
php tools/rename-engine.php --engine=content
```
Expected: "Rename voltooid voor engine 'content'.". 
Check: `ls packages/cms-core/src/Content/` — moet `ContentRepository.php` bevatten.
Check: `test -f packages/cms-core/src/legacy/content.php || echo "legacy weg"` — expected "legacy weg".

- [ ] **Step 5: Update `legacy/bootstrap.php` — verwijder content.php uit lijst**

Edit `packages/cms-core/src/legacy/bootstrap.php`: verwijder de regel `'content.php',` uit de `$engines` array.

- [ ] **Step 6: Run alle tests + integratie**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Expected: alle tests pass (inclusief nieuwe ContentRepositoryTest en bestaande PageRenderingTest).

Als PageRenderingTest faalt: er is een caller die de rename-script gemist heeft (bijv. in een dynamisch `eval()` of template-include die nikic/php-parser niet kon traceren). Grep naar overgebleven calls:
```bash
grep -rn "site(" apps/ packages/cms-core/templates/ packages/cms-core/beheer/ | grep -v "ContentRepository\|siteValue\|//\|#"
```
Fix handmatig waar nodig — gebruik `use Easeo\Cms\Content\ContentRepository;` + `ContentRepository::siteValue(...)`.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Fase B: engine content → Easeo\\Cms\\Content\\ContentRepository"
```

---

### Task B4: Engine 2 — `lang`

**Files:**
- Deleted: `packages/cms-core/src/legacy/lang.php`
- Created: `packages/cms-core/src/Lang/Translator.php`
- Created: `packages/cms-core/tests/Lang/TranslatorTest.php`

- [ ] **Step 1: Test schrijven**

Create `packages/cms-core/tests/Lang/TranslatorTest.php`:
```php
<?php
namespace Easeo\Cms\Tests\Lang;

use Easeo\Cms\Lang\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase {
    public function testTranslateReturneertNederlandsStandaard(): void {
        $result = Translator::translate('greeting');
        $this->assertIsString($result);
    }

    public function testAvailableLocalesBevatNLEnEN(): void {
        $locales = Translator::availableLocales();
        $this->assertContains('nl', $locales);
        $this->assertContains('en', $locales);
    }
}
```

- [ ] **Step 2: Dry-run**

```bash
php tools/rename-engine.php --engine=lang --dry-run
```
Lees output — bevestig dat alle `t('...')` calls in templates + beheer worden geraakt (vaak >100 calls).

- [ ] **Step 3: Execute**

```bash
php tools/rename-engine.php --engine=lang
```

- [ ] **Step 4: Update bootstrap + tests**

Edit `packages/cms-core/src/legacy/bootstrap.php`: verwijder `'lang.php',`.

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Expected: alle tests pass.

Als PageRenderingTest nu faalt met "Call to undefined function t()": rename-script heeft niet alle templates kunnen parseren (bijv. inline PHP in HEREDOC-strings). Grep + fix handmatig:
```bash
grep -rn "\bt(" packages/cms-core/templates/ packages/cms-core/beheer/ apps/ | grep -v "Translator::\|isset\|//\|#"
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Fase B: engine lang → Easeo\\Cms\\Lang\\Translator"
```

---

### Task B5: Engine 3 — `brand`

Herhaal patroon van B3/B4:

- [ ] **Step 1: Test schrijven** (`packages/cms-core/tests/Branding/BrandConfigTest.php`)

```php
<?php
namespace Easeo\Cms\Tests\Branding;

use Easeo\Cms\Branding\BrandConfig;
use PHPUnit\Framework\TestCase;

class BrandConfigTest extends TestCase {
    public function testCssPropertiesRetourneertRootBlock(): void {
        $css = BrandConfig::cssProperties();
        $this->assertStringContainsString(':root', $css);
        $this->assertStringContainsString('--color-primary', $css);
        $this->assertStringContainsString('--font-display', $css);
    }

    public function testGoogleFontsUrlBevatFontsGoogleapis(): void {
        $url = BrandConfig::googleFontsUrl();
        $this->assertStringStartsWith('https://fonts.googleapis.com/css2?', $url);
    }
}
```

- [ ] **Step 2: Dry-run**

```bash
php tools/rename-engine.php --engine=brand --dry-run
```

- [ ] **Step 3: Execute**

```bash
php tools/rename-engine.php --engine=brand
```

- [ ] **Step 4: Update bootstrap**

Edit `packages/cms-core/src/legacy/bootstrap.php`: verwijder `'brand.php',`.

- [ ] **Step 5: Run tests**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Expected: alle tests pass.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Fase B: engine brand → Easeo\\Cms\\Branding\\BrandConfig"
```

---

### Task B6: Engine 4 — `mailer`

- [ ] **Step 1: Test** (`packages/cms-core/tests/Mail/MailerTest.php`)

```php
<?php
namespace Easeo\Cms\Tests\Mail;

use Easeo\Cms\Mail\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase {
    public function testEncryptDecryptRoundtrip(): void {
        $plain = 'test-wachtwoord-123';
        $encrypted = Mailer::encryptSmtpPassword($plain);
        $this->assertNotSame($plain, $encrypted);

        $decrypted = Mailer::decryptSmtpPassword($encrypted);
        $this->assertSame($plain, $decrypted);
    }
}
```

- [ ] **Step 2: Dry-run + execute**

```bash
php tools/rename-engine.php --engine=mailer --dry-run
php tools/rename-engine.php --engine=mailer
```

- [ ] **Step 3: Update bootstrap, run tests**

Edit `bootstrap.php`: verwijder `'mailer.php',`.
```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "Fase B: engine mailer → Easeo\\Cms\\Mail\\Mailer"
```

---

### Task B7: Engine 5 — `form-engine`

- [ ] **Step 1: Test** (`packages/cms-core/tests/Form/FormEngineTest.php`)

```php
<?php
namespace Easeo\Cms\Tests\Form;

use Easeo\Cms\Form\FormEngine;
use PHPUnit\Framework\TestCase;

class FormEngineTest extends TestCase {
    public function testGetFormReturnsNullVoorNonexistentId(): void {
        $this->assertNull(FormEngine::getForm('does-not-exist'));
    }
}
```

- [ ] **Step 2-4**: dry-run → execute → bootstrap update → tests → commit met bericht `"Fase B: engine form-engine → Easeo\\Cms\\Form\\FormEngine"`.

---

### Task B8–B13: Resterende Categorie-1 engines (function-to-class via rename-script)

Herhaal het patroon van B5/B6/B7 voor elke resterende Categorie-1 engine in deze volgorde (minst-afhankelijke eerst):

1. **B8** — `audit` → `Easeo\Cms\Audit\AuditLogger`
2. **B9** — `legal` → `Easeo\Cms\Legal\LegalPages`
3. **B10** — `structured-data` → `Easeo\Cms\Seo\StructuredData`
4. **B11** — `navigation` → `Easeo\Cms\Navigation\Menu`
5. **B12** — `media-engine` → `Easeo\Cms\Media\MediaLibrary`
6. **B13** — `blog-engine` → `Easeo\Cms\Blog\BlogEngine` (grootste — 13 functies, doe als laatste)

**Voor elke engine, 5 stappen:**

- [ ] **Step 1**: Schrijf een minimale test voor de nieuwe class (`packages/cms-core/tests/<Subdir>/<Class>Test.php`) — minstens 1 test per public method, of 1 smoketest als de engine veel side-effects heeft.

- [ ] **Step 2**: Dry-run + lees output:
```bash
php tools/rename-engine.php --engine=<engine> --dry-run
```

- [ ] **Step 3**: Execute:
```bash
php tools/rename-engine.php --engine=<engine>
```

- [ ] **Step 4**: Update `packages/cms-core/src/legacy/bootstrap.php` — verwijder regel `'<engine>.php',` uit `$engines` array. Run full test-suite:
```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```
Fix eventuele overgebleven call-sites handmatig (grep + `use`-statement + static-call).

- [ ] **Step 5**: Commit:
```bash
git add -A
git commit -m "Fase B: engine <engine> → <FullyQualifiedClass>"
```

**Tip voor moeilijke engines** (`blog-engine`, `media-engine`): deze hebben >10 functies. Als rename-script faalt op een specifieke functie (bijv. door referenties of `func_get_args()`): laat de functie weg uit de mapping JSON en migreer hem daarna handmatig in een aparte commit. Voeg in de nieuwe class een method toe die de logica letterlijk overneemt:
```php
public static function lastigeMethode(mixed $arg): mixed {
    // migreerd handmatig — zie commit abc123
    // ... inhoud van oude functie ...
}
```

---

### Task B14: Categorie-2 engine — `rate-limiter` (namespacing-only)

**Files:**
- Deleted: `packages/cms-core/src/legacy/rate-limiter.php`
- Created: `packages/cms-core/src/Security/RateLimiter.php`
- Created: `packages/cms-core/tests/Security/RateLimiterTest.php`

`rate-limiter.php` bevat al een PHP-class `RateLimiter` zonder namespace. Hier is géén AST-rewrite nodig — alleen een namespace-declaratie toevoegen en callers updaten.

- [ ] **Step 1: Schrijf test**

Create `packages/cms-core/tests/Security/RateLimiterTest.php`:
```php
<?php
namespace Easeo\Cms\Tests\Security;

use Easeo\Cms\Security\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {
    public function testNieuwIpIsNietLimited(): void {
        $rl = new RateLimiter(maxAttempts: 5, windowSeconds: 900, context: 'test-' . uniqid());
        $this->assertFalse($rl->isLimited('192.0.2.1'));
    }

    public function testHitVerhoogtTellerTotLimiet(): void {
        $context = 'test-' . uniqid();
        $rl = new RateLimiter(maxAttempts: 3, windowSeconds: 900, context: $context);
        $ip = '192.0.2.2';
        $rl->hit($ip); $rl->hit($ip); $rl->hit($ip);
        $this->assertTrue($rl->isLimited($ip));
        $rl->reset($ip);
        $this->assertFalse($rl->isLimited($ip));
    }
}
```

- [ ] **Step 2: Verplaats file + voeg namespace toe**

```bash
mkdir -p packages/cms-core/src/Security
git mv packages/cms-core/src/legacy/rate-limiter.php packages/cms-core/src/Security/RateLimiter.php
```

Edit `packages/cms-core/src/Security/RateLimiter.php`, voeg na `<?php` toe:
```php
<?php
namespace Easeo\Cms\Security;

// (bestaande code blijft ongewijzigd)
```

- [ ] **Step 3: Update callers**

Grep naar `new RateLimiter` in alle caller-dirs:
```bash
grep -rn "new RateLimiter" apps/easeo-website/ packages/cms-core/ | grep -v "src/Security"
```

Elk gevonden caller-file: voeg `use Easeo\Cms\Security\RateLimiter;` toe aan de top.

- [ ] **Step 4: Bootstrap update**

Edit `packages/cms-core/src/legacy/bootstrap.php`: verwijder `'rate-limiter.php',`.

- [ ] **Step 5: Tests + commit**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
git add -A
git commit -m "Fase B: rate-limiter → Easeo\\Cms\\Security\\RateLimiter (namespacing-only)"
```

---

### Task B15: Categorie-3 engines — template-files verplaatsen

**Files verplaatst:**
- `packages/cms-core/src/legacy/header.php` → `packages/cms-core/templates/layout/header.php`
- `packages/cms-core/src/legacy/footer.php` → `packages/cms-core/templates/layout/footer.php`
- `packages/cms-core/src/legacy/cookie-consent.php` → `packages/cms-core/templates/layout/cookie-consent.php`
- `packages/cms-core/src/legacy/tracking-head.php` → `packages/cms-core/templates/layout/tracking-head.php`
- `packages/cms-core/src/legacy/tracking-body.php` → `packages/cms-core/templates/layout/tracking-body.php`

Deze 5 files bevatten geen top-level PHP-functies — alleen HTML + inline `<?= ?>`-tags en (voor cookie-consent) JS-functies binnen een `<script>`-block. Ze horen thuis als templates, niet als engines.

- [ ] **Step 1: Verplaats files**

```bash
mkdir -p packages/cms-core/templates/layout
git mv packages/cms-core/src/legacy/header.php         packages/cms-core/templates/layout/header.php
git mv packages/cms-core/src/legacy/footer.php         packages/cms-core/templates/layout/footer.php
git mv packages/cms-core/src/legacy/cookie-consent.php packages/cms-core/templates/layout/cookie-consent.php
git mv packages/cms-core/src/legacy/tracking-head.php  packages/cms-core/templates/layout/tracking-head.php
git mv packages/cms-core/src/legacy/tracking-body.php  packages/cms-core/templates/layout/tracking-body.php
```

- [ ] **Step 2: Update callers — `require_once ... header.php` → nieuwe path**

Grep naar includes/header/footer/cookie-consent etc in entries:
```bash
grep -rn "includes/header\|legacy/header\|includes/footer\|legacy/footer\|cookie-consent\|tracking-" apps/easeo-website/public/ packages/cms-core/beheer/ | grep -i "require\|include"
```

Elke gevonden include update naar nieuwe path via `EASEO_TEMPLATES` constant:
```php
require __DIR__ . '/includes/header.php';
```
Wordt:
```php
require EASEO_TEMPLATES . '/layout/header.php';
```

- [ ] **Step 3: Bootstrap update**

Edit `packages/cms-core/src/legacy/bootstrap.php`: verwijder alle 5 regels (`'header.php',`, `'footer.php',`, `'cookie-consent.php',`, `'tracking-head.php',`, `'tracking-body.php',`).

**Let op:** sommige van deze files roepen `site(...)` (nu `ContentRepository::siteValue`) aan. Na B3 is die helper al omgezet — controleer dat templates deze via de juiste use-statement callen. Als templates niet autoload-bereikbaar zijn voor `use`-statements: tijdelijk `use` bovenaan de template-file toevoegen, of switchen naar FQN in-line: `<?= \Easeo\Cms\Content\ContentRepository::siteValue('brand.name') ?>`.

- [ ] **Step 4: Integratie-test**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
```

PageRenderingTest is de beste test hier — als homepage rendert met correcte header+footer, zijn de templates goed ingelezen.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Fase B: Categorie-3 engines → templates/layout/ (5 files)"
```

---

### Task B16: Legacy-bridge en `src/legacy/` opruimen

**Files:**
- Deleted: `packages/cms-core/src/legacy/` (hele directory)
- Deleted: `tools/legacy-bridge.php`
- Modified: `packages/cms-core/composer.json` (files-autoload weg)

- [ ] **Step 1: Verifieer `src/legacy/` is leeg**

```bash
ls packages/cms-core/src/legacy/
```
Expected: alleen `bootstrap.php` (met lege `$engines = []`) en eventueel `phpmailer/` (die wordt in latere plan vervangen door `composer require phpmailer/phpmailer`).

Als er nog engine-files liggen: task B3-B18 is niet compleet. Keer terug naar de desbetreffende task.

- [ ] **Step 2: Verplaats `phpmailer/` naar `vendor-legacy/`**

```bash
git mv packages/cms-core/src/legacy/phpmailer packages/cms-core/vendor-legacy/phpmailer
```
(Dit wordt in Plan 2 of 3 vervangen door officiële Composer `phpmailer/phpmailer` dependency.)

Update `packages/cms-core/src/Mail/Mailer.php` (en eventuele andere callers): `require_once __DIR__ . '/../../src/legacy/phpmailer/...'` → `require_once __DIR__ . '/../../vendor-legacy/phpmailer/...'`. Gebruik grep om alle referenties te vinden.

- [ ] **Step 3: Verwijder legacy-bootstrap**

```bash
rm -rf packages/cms-core/src/legacy
```

- [ ] **Step 4: Update `packages/cms-core/composer.json`**

Edit `packages/cms-core/composer.json`: verwijder het `files`-blok uit autoload:
```json
{
    "autoload": {
        "psr-4": {
            "Easeo\\Cms\\": "src/"
        }
    },
    ...
}
```

- [ ] **Step 5: Verwijder `tools/legacy-bridge.php`**

Maar behoud de constants (EASEO_ROOT, EASEO_APP, EASEO_DATA, etc.) — die zijn nog nodig. Verplaats ze naar een nieuwe `packages/cms-core/src/Constants.php`:

```php
<?php
namespace Easeo\Cms;

class Constants {
    public static function bootstrap(string $appRoot): void {
        if (!defined('EASEO_APP'))       define('EASEO_APP',       $appRoot);
        if (!defined('EASEO_DATA'))      define('EASEO_DATA',      $appRoot . '/data');
        if (!defined('EASEO_CORE'))      define('EASEO_CORE',      dirname($appRoot, 2) . '/packages/cms-core');
        if (!defined('EASEO_TEMPLATES')) define('EASEO_TEMPLATES', self::core() . '/templates');
        if (!defined('EASEO_LANG'))      define('EASEO_LANG',      self::core() . '/lang');
        if (!defined('EASEO_BEHEER'))    define('EASEO_BEHEER',    self::core() . '/beheer');
    }

    private static function core(): string {
        return defined('EASEO_CORE') ? EASEO_CORE : '';
    }
}
```

Update `apps/easeo-website/public/index.php` + andere entries: voeg na `require_once ... /vendor/autoload.php;` toe:
```php
\Easeo\Cms\Constants::bootstrap(dirname(__DIR__));
```
(doe dit voor elk entry-bestand dat constants nodig heeft — wat waarschijnlijk alles is behalve `sitemap.php` en `feed.php` — gebruik grep om te checken)

```bash
rm tools/legacy-bridge.php
```

- [ ] **Step 6: Full test-run**

```bash
composer dump-autoload
./vendor/bin/phpunit --configuration packages/cms-core/phpunit.xml
./vendor/bin/phpunit --configuration tools/phpunit.xml
```
Expected: alle tests pass.

- [ ] **Step 7: Integratie-smoketest — server starten**

```bash
cd apps/easeo-website
php -S localhost:8000 -t public public/router.php &
SERVER_PID=$!
sleep 1
curl -sI http://localhost:8000/ | head -1
curl -sI http://localhost:8000/blog/ | head -1
curl -sI http://localhost:8000/contact/ | head -1
curl -sI http://localhost:8000/sitemap.xml | head -1
kill $SERVER_PID
cd ../..
```
Expected: alle curl-calls tonen `HTTP/1.1 200 OK`.

Als één faalt: de desbetreffende entry-file heeft nog een legacy-constant of include die niet opgevangen is. Check de PHP error-log (bv. `ls apps/easeo-website/data/logs/`).

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "Fase B: legacy-bridge verwijderd + Constants class + phpmailer → vendor-legacy"
git tag fase-b-complete
```

---

### Task B17: Eind-regressietest + merge

- [ ] **Step 1: Vergelijk home-render vóór en na (visueel)**

Deze stap gaat over visuele verificatie — voer het uit in een browser:
```bash
cd apps/easeo-website
php -S localhost:8000 -t public public/router.php
```
Open in browser: http://localhost:8000/, http://localhost:8000/blog/, http://localhost:8000/contact/, http://localhost:8000/beheer/. Check:
- [ ] Homepage toont header, hero, content-blokken, footer
- [ ] Blog listing werkt
- [ ] Contact-form rendert (inzenden mag nog niet werken — SMTP komt in Plan 2)
- [ ] Beheer-login form rendert (login-flow mag falen — SMTP/sessions kunnen broken zijn tot Plan 2)

Als visueel iets kapot is: niet mergen. Maak een issue en los op.

- [ ] **Step 2: Merge naar main**

```bash
git checkout main
git merge monorepo-split
```
(Niet pushen — user beslist dat zelf.)

- [ ] **Step 3: CHANGELOG schrijven**

Create `packages/cms-core/CHANGELOG.md`:
```markdown
# Changelog — easeo/cms-core

## [Unreleased]
### Changed
- Omgezet van procedureel `includes/`-model naar PSR-4 library met namespace `Easeo\Cms\`.
- 17 engines hernoemd naar class-based APIs (zie `src/` directories).
- Legacy-bridge verwijderd, constants verplaatst naar `Easeo\Cms\Constants`.
```

Create `CHANGELOG.md` (root):
```markdown
# Changelog — easeo-cms monorepo

## [Unreleased]
### Changed
- Repo geconverteerd naar monorepo-structuur: `packages/cms-core` + `apps/easeo-website`.
- Automated AST rename-script (`tools/rename-engine.php`) gebouwd.

## Plan-status
- [x] Plan 01 — Monorepo-skelet + namespace-refactor
- [ ] Plan 02 — Config-split (12-factor)
- [ ] Plan 03 — Module-infra + hello + shop-skelet
- [ ] Plan 04 — Deploy + cutover
```

```bash
git add CHANGELOG.md packages/cms-core/CHANGELOG.md
git commit -m "Plan 01 compleet: CHANGELOG bijgewerkt"
```

---

## Self-review notities (voor executor)

**Bekende risico's tijdens uitvoering:**
1. **Rename-script mist een call-site** in een template met inline-PHP of HEREDOC — los op met handmatig grep + find/replace.
2. **PHPMailer vendored code** heeft interne requires die breken bij verplaatsing — Mailer-class mag tijdelijk absolute paden gebruiken; wordt in Plan 2/3 vervangen door Composer-dependency.
3. **Beheer-sessions** kunnen breken door constante-verschuiving — check `session_save_path` en cookie-path na Task B19.
4. **`site.json` location** — legacy code leest `EASEO_DATA/site.json`. Na verhuizing naar `apps/easeo-website/data/site.json` moeten alle callers de nieuwe constant gebruiken.

**Verificatie-checklist na elke engine-task:**
- [ ] `composer dump-autoload` draaide zonder warnings
- [ ] PHPUnit alle tests pass
- [ ] Integratie-test (homepage 200 OK) pass
- [ ] Geen losse `require_once.*legacy` statements meer in caller-dirs (`grep -rn "require.*legacy" apps/ packages/`)

**Eind-state:**
- `packages/cms-core/src/` bevat 17+ namespaced classes in juiste subdirs
- `packages/cms-core/src/legacy/` bestaat niet meer
- `tools/legacy-bridge.php` bestaat niet meer
- `includes/`, `templates/`, `beheer/`, `lang/` in repo-root bestaan niet meer
- `apps/easeo-website/public/` bevat alle entry-PHP's
- Alle tests pass, homepage rendert correct

---
