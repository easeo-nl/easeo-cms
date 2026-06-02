# Troubleshooting

## Eerste stap: run de doctor

```bash
./bin/easeo-doctor                          # lokaal
./bin/easeo-doctor --remote=user@host:port  # op de server
```

De doctor geeft direct een overzicht van wat mis is.

## Veelvoorkomende problemen

### HTTP 500 na deploy

1. Controleer PHP-error log:
   ```bash
   ssh user@host -p 65002
   tail -50 ~/logs/error.log
   ```
2. Controleer of autoload klopt:
   ```bash
   php vendor/bin/phpcs --standard=PSR12 src/ 2>&1 | head -20
   ```
3. Snel herstel: rollback via symlink (zie `easeo-explain rollback`).

### Contactformulier stuurt geen mail

1. Controleer `.env` op SMTP-credentials:
   ```bash
   grep -i smtp .env
   ```
2. Test SMTP handmatig:
   ```bash
   php -r "
   \$m = new PHPMailer\PHPMailer\PHPMailer(true);
   \$m->isSMTP(); \$m->Host = getenv('SMTP_HOST');
   \$m->SMTPAuth = true; \$m->Username = getenv('SMTP_USER');
   \$m->Password = getenv('SMTP_PASS'); \$m->SMTPSecure = 'ssl';
   \$m->Port = 465;
   \$m->setFrom(getenv('MAIL_FROM')); \$m->addAddress('test@example.com');
   \$m->Subject = 'test'; \$m->Body = 'ok';
   \$m->send(); echo 'Verstuurd';
   "
   ```
3. Controleer spam-folder van ontvanger.

### GTM verdwenen na deploy

Dit triggert de post-deploy smoke test. Als het toch doorkwam:

1. Open `/beheer/` → Tracking → controleer GTM-id.
2. Controleer `site.config.json` in git op `gtm_id` veld.
3. Herstel het GTM-id en deploy opnieuw.

### data/ is read-only

```bash
chmod 755 ~/shared/data/
# Of als je geen rechten hebt:
php -r "echo is_writable('data/') ? 'writable' : 'read-only';"
```

### Migratie lock blijft staan

```bash
ssh user@host -p 65002
rm ~/shared/data/.migration.lock
```

### Composer autoload werkt niet

```bash
cd ~/domains/<site>.nl/public_html
composer dump-autoload --optimize
```

### Site laadt heel langzaam

1. Check OPcache-status:
   ```bash
   php -r "var_dump(opcache_get_status());" | grep -i hit
   ```
2. Check of OPcache gereset is na deploy (VersionTracker doet dit automatisch).
3. Controleer of database-queries traag zijn via `data/audit.log` timestamps.

## Log-locaties

| Log | Pad |
|-----|-----|
| PHP errors | `~/logs/error.log` |
| CMS audit-log | `~/shared/data/audit.log` |
| Webserver access | `~/logs/access.log` |
| Cron output | `~/logs/cron.log` |

## Escalatie

Niet opgelost na 15 minuten troubleshooten? Ping Nick via Slack met:
- URL van de site
- Foutmelding uit de logs
- Wat je al geprobeerd hebt
- Output van `./bin/easeo-doctor --remote=...`
