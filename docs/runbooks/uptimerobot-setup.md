# UptimeRobot setup voor EASEO klant-sites

Eenmalige setup per site. De gratis tier dekt 50 monitors met een 5-minuten interval —
ruim genoeg voor de huidige klant-sites.

## Stap 1 — Account aanmaken

1. Ga naar https://uptimerobot.com/signUp
2. Maak een account met het EASEO-emailadres (`monitoring@easeo.nl` of `ikben@nickaldewereld.nl`)
3. Verifieer het emailadres
4. (Aanbevolen) maak een sub-account voor het EASEO-team zodat Ardo ook toegang heeft

## Stap 2 — Monitors per klant-site

Voor elke klant-site twee monitors aanmaken:

### Monitor A — homepage HTTP 200

| Veld | Waarde |
|------|--------|
| Type | HTTP(s) |
| Friendly Name | `<site>.nl — homepage` |
| URL | `https://www.<site>.nl/` |
| Interval | 5 minuten |
| Alert Contacts | jouw email + (optioneel) Slack webhook |

**Optioneel: Keyword check**
Activeer "Keyword Exists" met zoekterm `googletagmanager.com/gtm.js` om direct
alarm te slaan als het GTM-script verdwijnt uit de homepage (CMS-state loss indicator).

### Monitor B — healthcheck endpoint

| Veld | Waarde |
|------|--------|
| Type | HTTP(s) — Keyword |
| Friendly Name | `<site>.nl — healthcheck` |
| URL | `https://www.<site>.nl/healthcheck.php` |
| Interval | 5 minuten |
| Keyword Exists | `"status":"ok"` |

### Te monitoren sites

| Site | Homepage URL | Healthcheck URL |
|------|-------------|-----------------|
| easeo.nl | https://www.easeo.nl/ | https://www.easeo.nl/healthcheck.php |
| rwwbouw.nl | https://www.rwwbouw.nl/ | https://www.rwwbouw.nl/healthcheck.php |
| qpmarketing.nl | https://www.qpmarketing.nl/ | https://www.qpmarketing.nl/healthcheck.php |

## Stap 3 — E-mail alert contacts

1. UptimeRobot Dashboard → My Settings → Alert Contacts
2. Add Alert Contact → Type: Email
3. Voeg in ieder geval toe:
   - `ikben@nickaldewereld.nl` (Nick — altijd)
   - Ardo's emailadres (als jr-dev)

## Stap 4 — Slack-integratie (optioneel)

1. Ga naar je Slack workspace → Apps → "Incoming Webhooks" → Add to Workspace
2. Kies het kanaal (bijv. `#monitoring`)
3. Kopieer de Webhook URL
4. UptimeRobot → My Settings → Alert Contacts → Add → Type: Slack
5. Plak de Webhook URL

## Stap 5 — Public status page (optioneel)

Handig om klanten te laten zien dat alles draait:

1. UptimeRobot Dashboard → Status Pages → Add New
2. Naam: `EASEO Status`
3. Selecteer alle monitors die je publiek wilt tonen
4. Custom domain: `status.easeo.nl`
   - Voeg een CNAME record toe bij je DNS-provider:
     ```
     status.easeo.nl CNAME stats.uptimerobot.com
     ```
5. Sla op en activeer

## Wat te doen bij een alert

Wanneer UptimeRobot een alert stuurt (email/Slack):

1. **Open de UptimeRobot incident-detailpagina** (link in de alert-email/Slack-bericht)
2. **Run de doctor** om te zien wat er mis is:
   ```bash
   ./bin/easeo-doctor --remote=user@host:port
   # Bijv. voor easeo.nl (Hostinger):
   ./bin/easeo-doctor --remote=u123456789@hostname.hostinger.com:65002
   ```
3. **Controleer de server-logs**:
   ```bash
   ssh user@host -p 65002
   tail -50 ~/logs/error.log
   tail -20 ~/shared/data/audit.log
   ```
4. **GTM-alarm**: open `/beheer/` → Tracking → controleer + herstel GTM-id
5. **HTTP 500**: zie `easeo-explain troubleshooting` voor stappenplan
6. **Rollback nodig**: zie `easeo-explain rollback`

## Configuratie per site — inloggegevens bewaren

Bewaar UptimeRobot-logingegevens in 1Password / Bitwarden onder `EASEO — UptimeRobot`.

## Status hub (vul in na monitor-creatie)

| Site | Hosting | Monitor A ID | Monitor B ID | Status page |
|------|---------|-------------|-------------|-------------|
| easeo.nl | Hostinger | (invullen) | (invullen) | status.easeo.nl |
| rwwbouw.nl | Hostinger | (invullen) | (invullen) | status.easeo.nl |
| qpmarketing.nl | Hostinger | (invullen) | (invullen) | status.easeo.nl |
