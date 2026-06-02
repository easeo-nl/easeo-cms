# Server-prep — Capistrano-layout op Hostinger shared

Eenmalige restructure van een klant-site's webroot naar de Capistrano-style layout (`releases/`, `shared/`, `current` symlink). Vereist voor Plan 08 (easeo-website cutover) en Plans 10/11 (rww + qpmarketing cutover).

**Duur:** ~15 min per site. **Downtime:** ~10 sec (één symlink-swap).

## Voorbereiding

### 1. Communicatie

Stuur klant/dev een short notice:
> "Tussen 22:00-22:15 doen we een eenmalige directory-restructure op de server. Korte downtime ~10 sec. Het CMS blijft daarna identiek werken — geen content of GTM-id verandert."

### 2. Pre-prep snapshot

Tag de huidige klant-repo state als rollback-anker:
```bash
cd ~/projects/<klant-repo>
git tag pre-server-prep-$(date +%F)
git push --tags
```

## Server-prep stappen

SSH naar de Hostinger account:
```bash
ssh -p <DEPLOY_PORT> <DEPLOY_USER>@<DEPLOY_HOST>
cd ~/domains/<SITE_DOMAIN>
```

Verifieer huidige layout:
```bash
ls -la public_html/ | head
```
Verwacht: flat layout (`index.php`, `data/`, `images/`, etc. allemaal direct onder `public_html/`).

### Stap 1: Backup

```bash
mkdir -p ~/backups
tar -czf ~/backups/pre-capistrano-$(date +%F).tgz public_html/data public_html/images
ls -la ~/backups/pre-capistrano-*.tgz   # verifieer
```

### Stap 2: Maak shared/ + releases/

```bash
mkdir -p shared/data shared/images/uploads releases
```

### Stap 3: Verhuis state naar shared/

```bash
# data/ verhuizen
mv public_html/data/* shared/data/ 2>/dev/null || true
# alleen file-content; gitignored dotfiles ook
[ -f public_html/data/.htaccess ] && mv public_html/data/.htaccess shared/data/.htaccess

# images/uploads/ verhuizen
mv public_html/images/uploads/* shared/images/uploads/ 2>/dev/null || true
```

### Stap 4: Verplaats huidige public_html naar releases/legacy-…

```bash
DATE=$(date +%F)
mv public_html releases/legacy-$DATE
```

### Stap 5: Maak public_html als symlink + link shared/ in

```bash
ln -sfn releases/legacy-$DATE public_html

# Vervang nu de verhuisde dirs in de release door symlinks naar shared/:
rm -rf releases/legacy-$DATE/data releases/legacy-$DATE/images/uploads
ln -sfn "$PWD/shared/data" releases/legacy-$DATE/data
mkdir -p releases/legacy-$DATE/images
ln -sfn "$PWD/shared/images/uploads" releases/legacy-$DATE/images/uploads
```

### Stap 6: Smoke

```bash
curl -fsS https://www.<SITE_DOMAIN>/ | grep -q "googletagmanager.com/gtm.js" \
  && echo "OK: homepage + GTM intact" \
  || echo "FAIL — check + rollback (zie onder)"
```

Plus open in browser: `https://www.<SITE_DOMAIN>/` — verifieer visueel.

### Stap 7: Apache vhost / DocumentRoot

Voor Hostinger shared hosting: `public_html/` is altijd de DocumentRoot van het hosting-account. We gebruiken `public_html/` als een symlink dus dat blijft werken zonder vhost-changes — Apache volgt symlinks (mits `+FollowSymLinks` actief is, default op Hostinger).

Voor sites met een eigen `public/` subdirectory (de skeleton-pattern): Apache moet `public_html` → `releases/<sha>/public/` resolven. Test:
```bash
ls -la public_html/
# zou release-dir's inhoud moeten tonen
```

## Rollback (binnen ~5 min)

Als smoke faalt of er iets visueel kapot is:
```bash
# Stap 1: restore public_html
rm public_html
mv releases/legacy-$DATE public_html

# Stap 2: restore data/ + images/uploads/ in plaats
rm public_html/data 2>/dev/null
rm public_html/images/uploads 2>/dev/null
mkdir -p public_html/data public_html/images
mv shared/data/* public_html/data/
mv shared/images/uploads/* public_html/images/uploads/

# Stap 3: cleanup
rm -rf shared releases
```

State is volledig terug naar de flat layout. Geen data verloren. Onderzoek wat er mis was vóór tweede poging.

## Na succes — wat is er nu mogelijk

- **Code-deploy** kan vanaf nu via tarball-upload naar `releases/<sha>/`, gevolgd door symlink-swap. Zie `apps/_skeleton/.github/workflows/deploy.yml` voor het standaard pattern.
- **Klant-state** (`data/*.json`, `images/uploads/`) leeft in `~/shared/` en wordt door deploys nooit aangeraakt.
- **Rollback** is één commando: `ln -sfn releases/<vorige-sha> public_html`.

## Sites status

Vul in tijdens uitrol:

| Site | Domain | SSH host | Datum prep | Smoke OK? |
|------|--------|----------|------------|-----------|
| easeo.nl | easeo.nl | (jouw VPS) | | |
| RWW Bouw | rwwbouw.nl | 82.25.102.35:65002 | | |
| QP Marketing | qpmarketing.nl | (lookup hPanel) | | |

## Volgende stap

Na succesvolle server-prep voor een site, kan die site gemigreerd worden naar de thin-site-app pattern via Plan 08/10/11 cutover-checklist (zie `docs/runbooks/cutover-checklist.md`).
