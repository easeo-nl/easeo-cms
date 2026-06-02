# Backups

## Wat wordt er geback-upt?

| Wat | Locatie | Hoe vaak |
|-----|---------|----------|
| `data/` (SQLite, JSON, audit-log) | `~/shared/data/` | Dagelijks via cron |
| `images/uploads/` | `~/shared/images/uploads/` | Dagelijks via cron |
| `site.config.json` | opgeslagen in git | Bij elke commit |
| `.env` / `.env.local` | NIET in git — afzonderlijk backup | Handmatig |

## Hostinger automatische backup

Hostinger Shared/Business-plans maken dagelijks een snapshot van de volledige
account. Herstel via het Hostinger-controlepaneel:

1. Hostinger → Websites → Backups
2. Kies de gewenste datum
3. Herstel specifieke directory of volledige account

**Let op**: Hostinger-backups zijn per-account, niet per site. Bij herstel gaan
alle andere sites op hetzelfde account ook terug.

## Handmatige backup vóór risicovolle operaties

```bash
# Van lokaal: backup data/ naar lokale machine
rsync -avz --delete \
  -e "ssh -p 65002" \
  u123456789@<host>:~/shared/data/ \
  ./local-backup/data-$(date +%Y%m%d)/

# Backup images/uploads/
rsync -avz --delete \
  -e "ssh -p 65002" \
  u123456789@<host>:~/shared/images/uploads/ \
  ./local-backup/uploads-$(date +%Y%m%d)/
```

## Herstel

```bash
# Herstel data/ vanuit lokale backup
rsync -avz \
  ./local-backup/data-20260515/ \
  -e "ssh -p 65002" \
  u123456789@<host>:~/shared/data/

# Controleer schema-versie na herstel
./bin/easeo-doctor --remote=u123456789@<host>:65002
```

## Wat NIET in backup zit

- Webserver-configuratie (`.htaccess` staat wél in git)
- SSL-certificaten (worden automatisch hernieuwd door Hostinger / Let's Encrypt)
- PHP-FPM config (beheerd door Hostinger)

## Best practices

- Test backupherstel minimaal 1x per kwartaal in een staging-omgeving.
- Bewaar `.env`-bestanden geencrypt in een wachtwoordmanager (bijv. 1Password of Bitwarden).
- Na een groot data-incident: audit-log controleren (`data/audit.log`) voor tijdlijn.
