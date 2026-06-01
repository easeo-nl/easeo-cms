# State

## Wat is "state" in EASEO CMS?

State is alle data die buiten git wordt bewaard — dingen die per-installatie uniek zijn
en die deploys moeten overleven.

## State-locaties

```
~/shared/                    <- buiten de release-dir, gedeeld tussen alle deploys
  data/                      <- CMS-state
    .schema-version          <- huidig schema-versienummer
    .cms-core-version        <- geinstalleerde cms-core versie (OPcache-tracking)
    .migration.lock          <- tijdelijk lock tijdens migratie
    audit.log                <- wie heeft wat gedaan
    cms.db                   <- (optioneel) SQLite database
    content/                 <- JSON-gebaseerde content-bestanden
  images/
    uploads/                 <- geuploade afbeeldingen via beheer

public_html -> releases/<sha>/  <- atomic symlink (geen state hier!)
```

## Wat hoort NIET in state?

- Code → in git
- Site-configuratie (site_url, GTM-id, kleuren) → `site.config.json` in git
- Geheimen (SMTP-wachtwoord, API-keys) → `.env` (NIET in git)

## State na een deploy

Deploys raken state nooit — `data/` en `images/uploads/` zijn symlinks naar
`~/shared/` en vallen buiten de tarball.

## State bekijken

```bash
# Op de server (SSH)
ls -la ~/shared/data/
cat ~/shared/data/.schema-version
cat ~/shared/data/audit.log | tail -20

# Lokaal via doctor
./bin/easeo-doctor --remote=user@host:port
```

## State resetten (destructief!)

Alleen doen op staging, nooit op productie zonder backup:

```bash
ssh user@host -p 65002
rm -rf ~/shared/data/
mkdir -p ~/shared/data/
# Herstart site zodat bootstrap + migraties opnieuw draaien
```

## Geheimen als state

`.env` en `.env.local` zijn state: ze leven op de server maar staan niet in git.
Bewaar ze in een wachtwoordmanager (1Password / Bitwarden).

Vergeten welke env-vars er zijn? Bekijk:
```bash
./bin/easeo-explain state
cat packages/cms-core/src/Config/Environment.php  # bevat alle get()-aanroepen
```
