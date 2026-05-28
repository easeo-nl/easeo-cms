# DEPLOY.md — {{SITE_NAME}}

## TL;DR

Push naar `main` → GitHub Actions deployt. Geen handmatige stap nodig.

## Pipeline

```
git push origin main
        │
        ▼
[CI: build → deploy → smoke]
        │
        ▼
Server: tarball naar releases/<sha>/, symlink-swap, oude release blijft als backup
        │
        ▼
[Smoke test: homepage HTTP 200 + GTM script aanwezig + sitemap.xml]
```

Zie `.github/workflows/deploy.yml` voor de exacte stappen.

## Eenmalige setup (per server)

Required GitHub Secrets in `easeo-nl/{{SITE_REPO}}`:

| Naam | Waarde |
|------|--------|
| `DEPLOY_SSH_KEY` | Private key (ed25519, dedicated voor deze site) |
| `DEPLOY_KNOWN_HOSTS` | Output van `ssh-keyscan -p <port> <host>` |
| `DEPLOY_HOST` | Hostinger IP of hostname |
| `DEPLOY_USER` | Hostinger username (bv. `u1234567`) |
| `DEPLOY_PORT` | SSH-poort (Hostinger: vaak `65002`) |
| `DEPLOY_PATH` | Pad naar `public_html` op de server |

Server-side directory-layout (eenmalig door Nick via Plan 09 runbook):

```
~/domains/{{SITE_DOMAIN}}/
├── public_html → releases/<sha>/public      # symlink
├── releases/
│   └── <sha>/...                            # bewaar laatste 5
└── shared/
    ├── data/                                # CMS-state, nooit aangeraakt door deploys
    └── images/uploads/
```

## Rollback (als smoke faalt of klant meldt issue)

```bash
ssh -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST
cd ~/domains/{{SITE_DOMAIN}}
ls releases/                                  # bekijk beschikbare versies, nieuwste eerst
ln -sfn releases/<vorige-sha>/public public_html   # symlink terug, <10 sec downtime
curl -fsS https://www.{{SITE_DOMAIN}}/         # verifieer rollback werkt
```

Code-rollback raakt `shared/data/` niet — klant-state blijft intact bij wisselen van release.

## Troubleshooting

### Deploy faalt op "PHPUnit (PHP X) check expected"

Branch protection vereist groene CI op je PR vóór merge. Check de CI-run, fix de oorzaak.

### Smoke faalt op "GTM script niet meer aanwezig"

Iemand heeft het GTM-snippet uit `templates/` gehaald, OF `data/site.json` op de server ontbreekt. SSH-check:

```bash
cd ~/domains/{{SITE_DOMAIN}}/shared/data
cat site.json | grep gtm_id
```

Als leeg of ontbrekend: log in op `{{SITE_DOMAIN}}/beheer/` en vul GTM-id opnieuw in. Eerstvolgende deploy doet niets met `data/`, dus de fix is permanent.

### Deploy faalt halverwege (timeout, netwerk, …)

Symlink-deploy is atomic: zolang de swap-stap niet is uitgevoerd, draait de site nog op de vorige release. Re-run de Actions-workflow.

### Composer install faalt na een Dependabot-bump

Major bump van `easeo/cms-core` waarschijnlijk. Check de release notes voor breaking changes. Ping @{{BACKSTOP_HANDLE}} bij twijfel.

### "branch is up to date" maar deploy runt niet

Check `.github/workflows/deploy.yml` triggers: alleen `push: branches: [main]` en `workflow_dispatch`. Een push naar een andere branch triggert niets.

## Diagnostic commands (vanuit klant-repo lokaal)

```bash
./bin/easeo-doctor                          # status van installation
./vendor/bin/easeo-explain deploys          # uitleg van deploy-flow
./vendor/bin/easeo-explain rollback         # uitleg van rollback
```
