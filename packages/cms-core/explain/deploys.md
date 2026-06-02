# Deploys

## Hoe het werkt

Push naar `main` → GitHub Actions runt CI → tarball naar de server → atomic symlink-swap.

```
git push origin main
        |
        v
[CI: PHPUnit + lint]
        |
        v
[Build tarball — excludes data/ + images/uploads/]
        |
        v
[Upload + atomic swap via symlink]
        |
        v
[Smoke test: homepage HTTP 200 + GTM aanwezig]
```

## Belangrijke concepten

- **Atomic swap**: `public_html` is een symlink naar `releases/<sha>/`. Switchen is
  één commando, no downtime.
- **Shared dir**: `data/` en `images/uploads/` zijn symlinks naar `~/shared/`. Deploys
  raken klant-state nooit.
- **Smoke test post-deploy**: faalt CI als GTM-script weg is, zodat klant-data-loss
  direct gedetecteerd wordt.

## Veelvoorkomende issues

- **Deploy faalt op smoke**: GTM mogelijk verdwenen uit homepage HTML. Check site.config.json,
  herstel via /beheer/ → Tracking.
- **Deploy duurt te lang**: tarball is groot? Check `images/` op accidentele uploads in git.
- **SSH-key expired**: vraag Nick om de deployment-key te vernieuwen.

## Nuttige commando's

```bash
# Lokaal testen vóór push
composer test

# Handmatige smoke check na deploy
curl -Is https://www.<site>.nl/ | head -1
curl -s https://www.<site>.nl/ | grep -c 'googletagmanager'

# Bekijk recente deploys
gh run list --workflow=deploy.yml --limit=5
```

## Verder lezen

- `docs/DEPLOY.md` voor stap-voor-stap rollback procedure
- ADR 0003 (symlink-based deploys) voor architectuur-rationale
