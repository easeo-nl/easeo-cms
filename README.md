# EASEO CMS

Een lichtgewicht, zero-dependency PHP CMS ontworpen als WordPress-alternatief voor MKB-websites.

## Kenmerken

- **Geen database** — Alle data wordt opgeslagen in JSON-bestanden
- **Geen dependencies** — Puur PHP 8.x, vanilla JavaScript, Tailwind CSS via CDN
- **Setup wizard** — Volledig geconfigureerd in 5 stappen
- **Admin panel** — Donker thema, responsive, met drag-drop media uploads
- **Blog engine** — Posts, categorieën, RSS feed, Schema.org markup
- **Formulieren** — Visuele form builder met inbox en e-mail notificaties
- **SEO-ready** — Auto-sitemap, meta tags, Open Graph, robots.txt
- **Juridisch** — Privacyverklaring, voorwaarden en cookiebeleid met sjablonen
- **Cookie consent** — AVG/GDPR-compliant banner met localStorage
- **Tracking** — GTM, Google Analytics, Search Console, Google Ads, Facebook Pixel
- **Beveiliging** — CSRF tokens, bcrypt wachtwoorden, rate limiting, audit logging
- **Huisstijl** — Dynamische kleuren, lettertypen en logo via admin panel
- **Backup** — Download/herstel van alle data als ZIP

## Installatie

1. Upload alle bestanden naar uw webserver (PHP 8.0+ vereist)
2. Zorg dat de `data/` en `images/` mappen schrijfbaar zijn
3. Navigeer naar `https://uw-domein.nl/setup.php`
4. Volg de 5-stappen setup wizard

### Alternatief: via install.php

```bash
php install.php
```

Dit initialiseert alle data bestanden vanuit de templates.

## Mapstructuur

```
├── beheer/              # Admin panel
│   ├── assets/          # Admin CSS
│   ├── inc/             # Auth, helpers, layout
│   └── pages/           # Admin pagina's
├── css/                 # Custom CSS
├── data/                # JSON data bestanden (niet in git)
│   └── submissions/     # Formulier inzendingen
├── images/
│   ├── uploads/         # Media uploads (niet in git)
│   └── thumbs/          # Thumbnails (niet in git)
├── includes/            # Core PHP includes
├── templates/           # Herbruikbare sectie templates
├── index.php            # Homepage
├── blog.php             # Blog overzicht
├── blog-post.php        # Blog post pagina
├── contact.php          # Contact pagina
├── pagina.php           # Dynamische pagina renderer
├── setup.php            # Setup wizard
├── form-handler.php     # Formulier POST handler
├── feed.php             # RSS feed
├── sitemap.php          # XML sitemap
├── install.php          # Bootstrap / data initialisatie
└── site.template.json   # Template voor site.json
```

## Admin Panel

Toegankelijk via `/beheer/`. Standaard tabbladen:

- **Dashboard** — Overzicht en statistieken
- **Content** — Pagina-inhoud bewerken (auto field config)
- **Blog** — Posts aanmaken en beheren
- **Media** — Bestanden uploaden en beheren
- **Formulieren** — Form builder
- **Inbox** — Formulier inzendingen
- **Navigatie** — Menu editor (hoofd + footer)
- **Huisstijl** — Kleuren, lettertypen, logo
- **Tracking** — GTM, Analytics, Pixel instellingen
- **Redirects** — URL redirects beheren
- **Juridisch** — Privacyverklaring, voorwaarden, cookiebeleid
- **Gebruikers** — Gebruikersbeheer (admin only)
- **Activiteit** — Audit log (admin only)
- **Backup** — Download & herstel (admin only)

## Vereisten

- PHP 8.0+
- GD extensie (voor afbeelding resize/thumbnails)
- Schrijfrechten op `data/` en `images/`

## Licentie

Eigendom van EASEO. Niet distribueren zonder toestemming.

---

Powered by [EASEO](https://www.easeo.nl) — Digital Agency met kracht
