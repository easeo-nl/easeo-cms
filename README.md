# EASEO CMS

Een lichtgewicht, open-source PHP CMS gebouwd als alternatief voor WordPress. Geen database, geen frameworks, geen afhankelijkheden — draait op elke shared hosting met PHP 8.x.

## Waarom EASEO CMS?

WordPress is overkill voor de meeste MKB-websites. Het is traag, kwetsbaar, en vereist constante updates van plugins en thema's. EASEO CMS is gebouwd vanuit de praktijk: een CMS dat doet wat het moet doen, zonder de bloat.

- **Zero dependencies** — geen Composer, geen npm, geen build tools
- **JSON in plaats van MySQL** — geen database nodig, werkt overal
- **Veilig by design** — 2FA, CSRF, rate limiting, CSP headers, bcrypt
- **Klaar in 5 minuten** — upload, doorloop de wizard, klaar

## Kenmerken

- Admin panel met donker thema en responsive design
- Blog engine met RSS feed en Schema.org structured data
- Dynamische pagina's met parent/child structuur en menu-integratie
- Formulieren builder met inbox, e-mail notificaties (SMTP) en CSV export
- SEO: auto-sitemap, JSON-LD, meta tags, breadcrumbs
- Beveiliging: 2FA, CSRF, bcrypt, rate limiting, account lockout, CSP headers
- Huisstijl editor: kleuren, fonts en logo via admin
- Media library met upload, resize en thumbnails
- Backup, audit logging, cookie consent, tracking (GTM/GA4/Pixel)

## Vereisten

- PHP 8.1+ met extensies: `gd`, `json`, `openssl`, `mbstring`, `fileinfo`
- Apache met `mod_rewrite` (of PHP built-in server voor lokaal testen)
- Schrijfrechten op `data/` en `images/`

## Installatie

```bash
# Clone het repository
git clone https://github.com/easeo-nl/easeo-cms.git
cd easeo-cms

# Lokaal testen
php -S localhost:8000 router.php
```

Bezoek `http://localhost:8000` in de browser. De setup wizard begeleidt je door de configuratie.

### Productie (shared hosting)

1. Upload de bestanden naar je webserver (FTP of git deploy)
2. Bezoek het domein — `install.php` maakt de data bestanden aan
3. Doorloop de 5-stappen setup wizard
4. Configureer SMTP in admin voor e-mail notificaties

## Architectuur

```
index.php              Homepage
blog.php / blog-post.php   Blog
contact.php            Contactpagina
pagina-router.php      Dynamische pagina's

includes/              Core PHP (content, auth, forms, media, mail)
beheer/                Admin panel (donker thema)
templates/             Herbruikbare frontend sections
data/                  JSON data bestanden (git-ignored)
```

Alle data wordt opgeslagen als JSON in `data/`. Geen database setup, geen migraties, geen ORM. Content laden gaat via `load_json()`, opslaan via `save_json()` met file locking.

## Bijdragen

Pull requests zijn welkom. Fork het repository, maak je wijzigingen, en open een PR.

Conventies:
- PHP: `snake_case` voor variabelen en functies
- JSON keys: `snake_case`
- URL slugs: `kebab-case`
- Admin UI: Nederlands
- Commit messages: Nederlands, kort en beschrijvend

## Licentie

MIT License — zie [LICENSE.txt](LICENSE.txt)

## Auteur

**Nick Aldewereld** — [EASEO](https://www.easeo.nl)

---

Gebouwd als open-source alternatief voor de CMS-monopolies. Gebruik het, fork het, maak het beter.
