# Plan 10 — rww-website cutover naar composer + thin site-app (Fase F)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 08 (eigen site) ≥1 week stabiel productie draait.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Migreer `rww-website` (klant-repo voor rwwbouw.nl) van fork-style naar thin-site-app dat `composer require easeo/cms-core: ^1.0` gebruikt. Behoud alle live klant-state (GTM-ID, content, branding). Resultaat: Ardo merget Dependabot-PRs en deployt zonder Nick.

**Architectuur:**
- Repo blijft `easeo-nl/rww-website` (zelfde naam, dezelfde GitHub-org). Géén nieuwe repo — force-push naar `main` na verzekerde backup.
- Backup-strategie: tag `pre-cutover-snapshot` vóór force-push behoudt complete history voor noodgeval.
- Server is al voorbereid in Plan 09 task E5 (Capistrano-layout op rwwbouw.nl).
- Cutover via `instantiate-skeleton.php` (Plan 06 B9) → invullen rww-specifieke branding/templates → eerste deploy via nieuwe GitHub Actions workflow → smoke groen → 48u observatie.

**Tech Stack:** PHP 8.2 (Hostinger), composer 2.x, GitHub Actions, `easeo/cms-core: ^1.0` (van Packagist, gepublished in Plan 08 task D9).

**Afhankelijkheden:** Plan 05-09 allemaal afgerond, Plan 08 ≥1 week stabiel op easeo.nl, Plan 09 server-prep voor rwwbouw.nl gedaan, communicatie met Ardo over timing.

**Afbakening:** Géén feature-changes in de site zelf. Alleen infrastructuur-migratie. Branding/templates blijven visueel identiek.

---

## Bestandsstructuur

**Op `easeo-nl/rww-website` repo (force-push na backup-tag):**

**Aangemaakt (volledige nieuwe inhoud):**
- `composer.json` — require `easeo/cms-core: ^1.0`
- `composer.lock`
- `public/index.php`
- `public/.htaccess`
- `public/assets/{css,js,images}/...` — rww-specifieke statische assets (uit huidige `css/`, `js/`, `images/`)
- `templates/` — rww-specifieke template-overrides (hero-section met "RWW Bouw" branding, kleurenpalet uit `css/style.css`)
- `images/` — rww logo, hero-foto's (statisch, niet uploads)
- `site.template.json` — geïnitialiseerd met rww-defaults (lege GTM, primary_color = oranje rww, etc.)
- `data/.gitkeep`
- `.gitignore` (uit Plan 06 skeleton)
- `bin/easeo-doctor` (wrapper)
- `.github/workflows/{deploy,pr-check,dependabot-comment}.yml`
- `.github/dependabot.yml`
- `.github/PULL_REQUEST_TEMPLATE.md`
- `docs/{DEVELOPER,DEPLOY}.md` met rww-specifieke values
- `docs/adr/*.md` (uit Plan 06 skeleton)
- `CHANGELOG.md`
- `README.md`

**Verwijderd (was in oude repo):**
- `404.php`, `badkamer.php`, `blog.php`, `blog-post.php`, `contact.php`, `cookiebeleid.php`, `diensten.php`, `feed.php`, `form-handler.php`, `index.php` (root), `keuken.php`, `pagina.php`, `pagina-router.php`, `polski.php`, `privacyverklaring.php`, `sitemap.php` — allemaal core-routing, nu in cms-core
- `beheer/` — admin-UI, nu in cms-core
- `includes/`, `lang/` — core-libraries, nu in cms-core
- `install.php`, `router.php` — bootstrap, nu via cms-core App::boot()
- `Fotos/`, `Reviews werkspot rww bouw.docx` — onderzoeken: gebruikt door huidige site? Zo nee, weg. Zo ja, archiveer in `images/` of `docs/`.

---

## Tasks-outline

### Task F1: Communicatie + window-planning
Vraag Ardo/Sylvester: hoe lang mag CMS "frozen" zijn? Plan window van max 30 min (live cutover + rollback-marge). Communiceer datum/tijd schriftelijk via email of WhatsApp.

### Task F2: Pre-cutover backup
SSH naar rwwbouw.nl, full tarball van `~/domains/rwwbouw.nl/shared/data/` + `shared/images/uploads/` + `releases/legacy-*/` (= complete pre-cutover state, want server is al in Capistrano-layout door Plan 09 E5). Download lokaal naar `~/backups/rww-cutover-YYYY-MM-DD/`.

### Task F3: Git-tag rollback-anker
In `easeo-nl/rww-website` repo:
```
git tag pre-cutover-snapshot
git push --tags
```

### Task F4: Genereer nieuwe repo-inhoud
Run `php /mnt/nvme1tb/projects/easeo-cms/tools/instantiate-skeleton.php --site=rww --domain=rwwbouw.nl --reviewer=<ardo-handle> --backstop=<nick-handle> --output=/tmp/rww-bootstrap/`. Verifieer in `/tmp/rww-bootstrap/`: composer install werkt, lokaal opstarten met `cp -r ~/backups/rww-cutover-*/data/* data/` toont de echte rww-content.

### Task F5: Branding/templates port
Vergelijk huidige `rww-website/css/` met `cms-core/templates/`-defaults. Maak `templates/`-overrides voor wat afwijkt (RWW-oranje, hero-section, eventueel custom contact-form layout). Test lokaal: rendering moet visueel identiek zijn aan de huidige live site.

### Task F6: Force-push nieuwe inhoud naar `easeo-nl/rww-website`
```
cd /tmp/rww-bootstrap/
git init && git add . && git commit -m "Cutover naar thin-site-app stack — composer require easeo/cms-core"
git remote add origin git@github.com:easeo-nl/rww-website.git
git push --force origin main
```
Tag `pre-cutover-snapshot` blijft als rollback-anker beschikbaar.

### Task F7: GitHub Secrets setup
Per spec sectie 8: `DEPLOY_SSH_KEY` (eigen key voor rww-website, niet hergebruikt van easeo.nl), `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PORT`, `DEPLOY_PATH`, `DEPLOY_KNOWN_HOSTS`. Genereer nieuw keypair, pubkey naar `~/.ssh/authorized_keys` op Hostinger, private key in GitHub Secrets.

### Task F8: Eerste deploy via Actions
Push triggert deploy.yml (Plan 06 template). Watch het end-to-end: build job → deploy job (rsync naar `releases/<sha>/`, symlink-swap) → smoke job (homepage 200, GTM aanwezig).

### Task F9: Live verifieer + DNS-snapshot
Open https://www.rwwbouw.nl/ in incognito. Verifieer: visueel identiek, GTM tag laadt in Network tab, contact-form werkt, admin login werkt op `/beheer/`. Annotation in UptimeRobot voor incident-correlation.

### Task F10: 48u observatie + rollback-criteria
Monitor UptimeRobot + check Sylvester/Ardo voor klachten. Rollback-criteria: smoke-test faalt, klant meldt content-verlies, 5xx-rate > 1%. Rollback = SSH, `ln -sfn releases/legacy-* current`, < 30s.

### Task F11: Legacy-cleanup + plan-completion
Geen rollback nodig → verwijder `releases/legacy-*` op server, vrij ~50 MB. Update CHANGELOG in rww-website met "Cutover voltooid YYYY-MM-DD". Hand-off email naar Ardo: "rww-website draait nu op thin-site-app stack. Volgende cms-core update komt als Dependabot-PR — review checklist staat in de PR-comment."

---

## Plan-status

| Task | Status |
|---|---|
| F1 — communicatie + window | ☐ |
| F2 — pre-cutover backup | ☐ |
| F3 — git-tag rollback-anker | ☐ |
| F4 — genereer nieuwe repo-inhoud | ☐ |
| F5 — branding/templates port | ☐ |
| F6 — force-push naar repo | ☐ |
| F7 — GitHub Secrets setup | ☐ |
| F8 — eerste deploy via Actions | ☐ |
| F9 — live verify + DNS-snapshot | ☐ |
| F10 — 48u observatie | ☐ |
| F11 — legacy-cleanup + hand-off | ☐ |
