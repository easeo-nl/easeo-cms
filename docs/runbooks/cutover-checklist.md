# Klant-site cutover — checklist (Plans 08, 10, 11)

Eenmalige migratie van een klant-repo van de oude fork-style naar de thin-site-app pattern op `easeo/cms-core: ^0.2`. Plan 09 server-prep moet eerst gedaan zijn voor dezelfde site.

**Duur:** ~30-45 min per site. **Downtime:** ~10 sec (atomic symlink-swap door de eerste Actions-deploy).

## Pre-cutover (jij, 30 min vooraf)

- [ ] Plan 09 server-prep is gedaan voor deze site (releases/+shared/+symlink layout)
- [ ] Klant-repo `pre-cutover-snapshot` tag bestaat als rollback-anker
- [ ] SSH-key voor de Hostinger account ligt klaar
- [ ] `apps/_skeleton/` is up-to-date op main van `easeo-nl/easeo-cms`
- [ ] Klant + dev geïnformeerd over de cutover-window

## Cutover

### 1. Genereer klant-repo content uit skeleton

Vanuit een lokale clone van `easeo-nl/easeo-cms`:
```bash
php tools/instantiate-skeleton.php \
  --site="<KLANT_NAAM>" \
  --domain=<SITE_DOMAIN> \
  --reviewer=<DEV_GITHUB_HANDLE> \
  --backstop=nick-aldewereld \
  --output=/tmp/<klant>-bootstrap/
```

Verifieer in `/tmp/<klant>-bootstrap/`:
- [ ] `composer install` succeeds
- [ ] Geen placeholders meer (`grep -rE '\{\{[A-Z_]+\}\}' /tmp/<klant>-bootstrap/` returns niets)
- [ ] `.github/workflows/deploy.yml` heeft de juiste site-domain in smoke-test URL

### 2. Genereer .env voor klant (vanuit huidige live state)

Op de klant-server, SSH:
```bash
cd ~/domains/<SITE_DOMAIN>/shared
cp data/site.json /tmp/site.json.cutover
exit
```

Lokaal:
```bash
scp <USER>@<HOST>:/tmp/site.json.cutover apps/easeo-website/data/site.json.<klant>-cutover
php tools/migrate-site-json-to-split.php --app=apps/easeo-website
# Inspecteer dry-run output: welke secrets worden gevonden?
php tools/migrate-site-json-to-split.php --app=apps/easeo-website --apply
# Verplaats het gegenereerde .env-block naar de klant-repo
cat apps/easeo-website/.env | grep -A 20 "auto-generated" > /tmp/<klant>.env-additions
```

### 3. Push initial commit naar klant-repo

```bash
cd /tmp/<klant>-bootstrap
git init
git add .
git commit -m "Cutover: thin-site-app op easeo/cms-core ^0.2"
git remote add origin git@github.com:easeo-nl/<klant>-website.git
git push --force origin main   # ALLEEN als pre-cutover-snapshot tag bestaat als anker
```

### 4. Server: upload .env

```bash
ssh <USER>@<HOST>
cd ~/domains/<SITE_DOMAIN>
# Maak ~/.env op de server (NIET in public_html — komt later in release-dir via symlink)
cat > shared/.env <<'EOF'
# (paste hier de inhoud van /tmp/<klant>.env-additions met de uncommented values)
EOF
chmod 600 shared/.env
```

### 5. Configureer GitHub Secrets

In `easeo-nl/<klant>-website` repo settings:
- `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_PATH`
- `DEPLOY_SSH_KEY` (dedicated keypair per klant, niet hergebruiken)
- `DEPLOY_KNOWN_HOSTS`
- `HEALTHCHECK_SECRET` (random string, ook in shared/.env)

### 6. Trigger eerste deploy

```bash
gh workflow run deploy.yml -R easeo-nl/<klant>-website
```

Watch de Actions run:
- [ ] Build job: composer install + lint + tarball — OK
- [ ] Deploy job: SSH + tarball upload + atomic symlink-swap — OK
- [ ] Smoke job: HTTP 200 + GTM script aanwezig — OK

### 7. Live verifieer (in browser)

- [ ] Homepage rendert visueel identiek aan vóór cutover
- [ ] GTM script laadt (check Network tab in DevTools)
- [ ] Sitemap.xml bereikbaar (`/sitemap.xml` → 200)
- [ ] Contact-form rendert
- [ ] Admin login werkt op `/beheer/`
- [ ] Een paar steekproef-pagina's (over, diensten, blog-post) renderen
- [ ] Healthcheck endpoint: `curl https://www.<SITE_DOMAIN>/healthcheck.php` → `{"status":"ok"}`
- [ ] UptimeRobot monitor zit op groen (zie `docs/runbooks/uptimerobot-setup.md`)

### 8. Annotation in monitoring

In UptimeRobot (en eventueel Sentry/Slack): markeer "Cutover voltooid `<datum>`" zodat incident-correlation tijdens 48u observatie helder is.

## 48u observatie

Monitor:
- UptimeRobot alerts (homepage HTTP + healthcheck)
- Klant-feedback (Sylvester/Ardo)
- 5xx-rate via server-side error_log

**Rollback-criteria** (één van de drie):
- Smoke-test faalt na een deploy
- Klant meldt content-verlies
- Server-side error-rate > 1% (PHP errors in error_log)

Rollback uitvoering:
```bash
ssh <USER>@<HOST>
cd ~/domains/<SITE_DOMAIN>
ls releases/   # vind de vorige release of legacy-XXXX
ln -sfn releases/<vorige-sha-of-legacy>/public public_html
# voor sites zonder /public substructure: ln -sfn releases/<vorige> public_html
curl -fsS https://www.<SITE_DOMAIN>/   # verifieer rollback werkt
```

`shared/data/` blijft intact — klant-content is veilig.

## Legacy cleanup (na 48u groen)

```bash
ssh <USER>@<HOST>
cd ~/domains/<SITE_DOMAIN>
ls releases/legacy-*  # check welke legacy-dir je had
rm -rf releases/legacy-<datum>
```

Klant heeft nu pure releases/<sha>/+shared/ setup zonder oude flat-layout backup.

## Hand-off email naar dev (Ardo/etc)

```
Onderwerp: <KLANT_NAAM>.nl draait nu op easeo/cms-core ^0.2

Hi <DEV_NAAM>,

De cutover is voltooid:
- Repo: https://github.com/easeo-nl/<klant>-website
- Live: https://www.<SITE_DOMAIN>/
- Healthcheck: https://www.<SITE_DOMAIN>/healthcheck.php
- UptimeRobot status: <link>

Wat is veranderd:
- Klant-repo bevat alleen branding + templates + deploy-config (~20 files i.p.v. 200)
- cms-core komt via Composer (vendor/easeo/cms-core/)
- data/*.json staat NIET in git — leeft alleen op de server (Capistrano shared/)
- Deploy via push naar main → GitHub Actions doet atomic symlink-swap

Updates aan cms-core komen vanaf nu als Dependabot-PRs in deze repo (wekelijks). Review-checklist staat als auto-comment op elke PR. Merge → CI deployt automatisch → smoke-test verifieert dat het GTM-script in de HTML staat en de homepage HTTP 200 geeft.

Bij twijfel:
- docs/DEVELOPER.md → 5-min onboarding
- docs/DEPLOY.md → rollback procedure + 5 troubleshooting scenarios
- ./bin/easeo-doctor → status van je installatie
- ./vendor/bin/easeo-explain <topic> → uitleg van concepten

Of ping mij (Nick) bij twijfel.

Groet,
Nick
```

## Sites status

| Site | Domain | Server-prep gedaan (Plan 09) | Cutover gedaan (deze runbook) | UptimeRobot zit |
|------|--------|------------------------------|-------------------------------|-------------------|
| easeo.nl | easeo.nl | | | |
| RWW Bouw | rwwbouw.nl | | | |
| QP Marketing | qpmarketing.nl | | | |
