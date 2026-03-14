# EASEO CMS

Zero-dependency PHP CMS voor MKB-websites. Vervangt WordPress met een lichtgewicht, veilig systeem dat draait op shared hosting (Hostinger). Gebruikt JSON-bestanden in plaats van een database. Enige externe dependency: PHPMailer voor SMTP e-mail.

## Tech Stack

- PHP 8.x (geen frameworks)
- JSON file storage (data/*.json)
- Tailwind CSS via CDN
- Google Fonts via CDN
- PHPMailer (includes/phpmailer/) voor SMTP
- Geen JavaScript frameworks

## Architectuur

### Bestandsstructuur

```
├── index.php                  # Homepage met template sections
├── blog.php                   # Blog overzicht met paginering
├── blog-post.php              # Individuele blogpost
├── contact.php                # Contactpagina met formulier
├── pagina.php                 # Legacy content.json pagina renderer
├── pagina-router.php          # Dynamische pagina's uit pages.json
├── form-handler.php           # Formulier POST handler
├── sitemap.php                # Auto-generated XML sitemap
├── feed.php                   # RSS feed
├── 404.php                    # 404 pagina
├── setup.php                  # 5-stappen setup wizard
├── install.php                # Bootstrap: maakt data bestanden aan
├── router.php                 # Dev router voor php -S
├── robots.txt                 # Crawler regels
├── .htaccess                  # Apache rewrites, security headers, caching
│
├── includes/
│   ├── content.php            # Core: load_json(), save_json(), site(), e()
│   ├── header.php             # HTML head + navigatie + structured data
│   ├── footer.php             # Footer + cookie consent + mobile menu JS
│   ├── navigation.php         # Menu rendering (handmatig + dynamisch uit pages.json)
│   ├── brand.php              # CSS variabelen, Tailwind config, Google Fonts URL
│   ├── blog-engine.php        # CRUD voor blogposts, paginering, post cards
│   ├── form-engine.php        # Formulier rendering, frontend CSRF
│   ├── media-engine.php       # Upload, resize, thumbnail, SVG sanitization
│   ├── mailer.php             # send_mail(): SMTP via PHPMailer of fallback mail()
│   ├── structured-data.php    # JSON-LD Schema.org generators
│   ├── audit.php              # Audit logging (JSON lines in audit.log)
│   ├── rate-limiter.php       # IP-based rate limiting
│   ├── tracking-head.php      # GTM, GA4, Facebook Pixel, custom head code
│   ├── tracking-body.php      # GTM noscript, custom body code
│   ├── cookie-consent.php     # Cookie consent banner
│   └── phpmailer/             # PHPMailer library (3 bestanden + loader)
│
├── beheer/
│   ├── index.php              # Admin router (?tab=), login, 2FA verificatie
│   ├── inc/
│   │   ├── auth.php           # Session, CSRF, login, 2FA, account lockout
│   │   ├── helpers.php        # Auto field config, render_field(), flash messages
│   │   ├── layout-top.php     # Admin HTML head + sidebar
│   │   └── layout-bottom.php  # Media picker modal + tooltip JS
│   └── pages/                 # 18 admin pagina's (dashboard, content, blog, etc.)
│
├── templates/                 # Herbruikbare frontend sections (hero, cta, text-block, etc.)
├── data/                      # JSON data bestanden (git-ignored, .htaccess protected)
├── images/uploads/            # Media uploads (.htaccess blokkeert PHP)
├── images/thumbs/             # Thumbnails (.htaccess blokkeert PHP)
└── css/custom.css             # Custom CSS
```

### Dataflow

1. Content staat in `data/*.json` bestanden
2. `includes/content.php` laadt JSON met caching via `load_json()`
3. Globale variabelen `$site`, `$content`, `$navigation` beschikbaar overal
4. Templates gebruiken `site()` voor config en `page_content()` voor pagina-data
5. Output altijd via `e()` (htmlspecialchars wrapper)
6. JSON schrijven via `save_json()` met LOCK_EX, PRETTY_PRINT, UNESCAPED_UNICODE

### Admin Panel

- Eén entry point: `beheer/index.php`
- Routing via `?tab=` GET parameter
- Elke tab laadt `beheer/pages/{tab}.php`
- Auth via session + CSRF tokens
- 2FA optioneel per gebruiker (e-mail codes)
- Session timeout: 30 minuten
- Account lockout: 10 mislukte pogingen = 15 min lock

### Formulieren

1. Definitie in `data/forms.json` (velden, validatie, e-mail routing)
2. Rendering via `render_form()` in `includes/form-engine.php`
3. Submission via `form-handler.php` (CSRF, honeypot, rate limit)
4. Opgeslagen in `data/submissions/{id}.json`
5. E-mail notificatie via `send_mail()` met reply-to op bezoeker
6. Inbox in admin panel: markeer gelezen, exporteer CSV

### Dynamische Pagina's

1. CRUD in `beheer/pages/paginas.php`
2. Data in `data/pages.json` (parent/child, templates, menu-integratie)
3. Frontend via `pagina-router.php`
4. URL routing: `.htaccess` → `pagina-router.php?slug=...`
5. Subpagina's: `/parent-slug/child-slug`
6. Menu-integratie: `show_in_menu` flag → automatisch in navigatie

### E-mail (SMTP)

1. Configuratie in `data/site.json` → `smtp` sectie
2. `includes/mailer.php` → `send_mail($to, $subject, $body, $reply_to)`
3. Kiest automatisch: SMTP via PHPMailer of fallback naar `mail()`
4. Wachtwoord versleuteld opgeslagen (AES-256-CBC)
5. Test e-mail functie in admin

## Security

- **Authenticatie**: bcrypt wachtwoorden, secure session cookies (httponly, secure, samesite=strict)
- **2FA**: Optioneel per account, 6-cijferige e-mail codes, 10 min geldig, max 3 pogingen
- **CSRF**: Tokens op alle POST requests (admin + frontend formulieren)
- **Rate limiting**: IP-based (5 pogingen/15 min) + account lockout (10 pogingen/15 min)
- **File uploads**: Extension whitelist, MIME check via finfo, SVG sanitization, .htaccess blokkeert PHP in upload dirs
- **Headers**: CSP, HSTS, X-Frame-Options, Permissions-Policy, X-Powered-By verwijderd
- **Data**: .htaccess op data/ directory, SMTP wachtwoord encrypted
- **Input**: strip_tags op SEO velden, slug enforcement [a-z0-9-/], tracking IDs gesanitized

## Deployment

### Hostinger Setup
1. Git auto-deploy via deploy keys
2. Fork van easeo-nl/easeo-cms
3. Bezoek domein → install.php maakt data bestanden → setup.php wizard

### Per-klant Workflow
1. Fork het repo
2. Deploy naar Hostinger
3. Doorloop de setup wizard (bedrijfsinfo, branding, pagina's, admin account)
4. Configureer SMTP in admin → E-mail instellingen

### Upstream Updates
```bash
git remote add upstream git@github.com:easeo-nl/easeo-cms.git
git fetch upstream
git merge upstream/main
```

## Conventies

### Naamgeving
- PHP variabelen/functies: `snake_case`
- JSON keys: `snake_case`
- URL slugs: `kebab-case`
- Admin tabs: lowercase, geen speciale tekens

### Auto Field Config
Veldnamen bepalen het veldtype in de content editor:
- `*_afbeelding`, `*_logo`, `*_foto` → media picker
- `*_tekst`, `*_inhoud`, `*_beschrijving` → textarea
- `*_email` → email input
- `*_url`, `*_link` → URL input
- `*_kleur`, `*_color` → color picker
- `*_aan`, `*_actief`, `*_toon` → checkbox
- `meta_title` → text (60 char SEO)
- `meta_description` → text (155 char SEO)

### JSON Schrijven
Altijd: `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` + `LOCK_EX`

### Admin UI
- Donker thema (gray-800/900 achtergrond)
- Inter font
- Tooltips via `<span class="help-tooltip" data-help="...">?</span>`
- Flash messages via session (success=groen, error=rood)
- Media picker modal via `openMediaPicker('input-id')`

### Taal
- Admin UI: Nederlands
- Code comments: Engels
- Variabelen: Nederlands (titel, inhoud, datum) in data, Engels in code
