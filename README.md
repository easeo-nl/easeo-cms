# EASEO CMS

Zero-dependency PHP CMS voor MKB-websites. Draait op shared hosting (Hostinger) zonder database — alle data wordt opgeslagen als JSON-bestanden. Enige externe dependency: PHPMailer voor SMTP e-mail.

## Vereisten

- PHP 8.1+ met extensies: `gd`, `json`, `openssl`, `mbstring`, `fileinfo`
- Apache met `mod_rewrite` (of PHP built-in server voor lokaal testen)
- Schrijfrechten op `data/` en `images/`

## Installatie

1. Clone of upload het repository naar je webserver
2. Bezoek het domein in de browser — `install.php` maakt de benodigde data bestanden aan
3. Doorloop de 5-stappen setup wizard (bedrijfsinfo, branding, content, admin account)

## Lokaal testen

```bash
php -S localhost:8000 router.php
```

## Kenmerken

- Admin panel met donker thema en responsive design
- Blog engine met categorien, RSS feed, Schema.org markup
- Dynamische pagina's met parent/child structuur en menu-integratie
- Formulieren builder met inbox, e-mail notificaties (SMTP), honeypot en rate limiting
- SEO: auto-sitemap, structured data (JSON-LD), meta tags, breadcrumbs
- Beveiliging: 2FA, CSRF, bcrypt, rate limiting, account lockout, CSP headers
- Huisstijl: dynamische kleuren, fonts en logo via admin
- Backup, audit logging, cookie consent, tracking (GTM/GA4/Pixel)

## Licentie

Eigendom van EASEO — zie LICENSE.txt voor details.

---

Powered by [EASEO](https://www.easeo.nl)
