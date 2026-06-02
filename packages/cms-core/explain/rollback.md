# Rollback

## Wanneer rollback nodig?

- Smoke test faalt na deploy (HTTP 500, GTM weg)
- Klant meldt kapotte pagina direct na deploy
- Migratie heeft data corrupt gemaakt

## Snelle rollback — symlink terugzetten (< 30 seconden)

```bash
ssh user@host -p 65002

cd ~/domains/<site>.nl/
ls releases/          # zie beschikbare releases

# Zet symlink terug naar vorige release
ln -snf releases/<vorige-sha>/ public_html
```

Na dit commando draait de oude code direct — geen webserver-herstart nodig.

## Controleer of rollback werkte

```bash
curl -Is https://www.<site>.nl/ | head -1      # HTTP 200?
curl -s https://www.<site>.nl/ | grep -c 'gtm' # GTM aanwezig?
```

## Rollback van migraties (zeldzaam)

EASEO-migraties hebben geen automatische `down()`. Bij dataschade:

1. Zet code terug via symlink (zie boven).
2. Herstel `data/` vanuit backup (zie `easeo-explain backups`).
3. Verlaag `.schema-version` handmatig naar het vorige versienummer:
   ```bash
   echo "1" > ~/shared/data/.schema-version
   ```
4. Verwijder `.migration.lock` als die aanwezig is.
5. Draai doctor om te verifiëren:
   ```bash
   ./bin/easeo-doctor --remote=user@host:port
   ```

## Rollback van .env wijzigingen

`.env` wordt niet beheerd door deploys. Maak bij twijfel een backup vóór wijzigen:
```bash
cp .env .env.backup-$(date +%Y%m%d)
```

## Escalatie

Als rollback niet helpt:
1. Zet site tijdelijk in onderhoudsmodus (hernoem `public_html/index.php` naar `maintenance.php`).
2. Bel Nick: hij heeft access tot Hostinger-controlepaneel voor dieper herstel.
3. Bekijk `data/audit.log` voor tijdlijn van wat er veranderd is.

## Na rollback

Altijd een post-mortem schrijven in het GitHub-issue:
- Wat was er mis?
- Hoe is het gedetecteerd?
- Welke stappen zijn gezet?
- Hoe voorkomen we dit de volgende keer?
