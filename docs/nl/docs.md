---
layout: default
title: Documentatie
description: Installatiehandleiding, architectuur en bijdrage-richtlijnen voor EASEO CMS.
lang: nl
---

<section class="docs-content">
<div class="container" markdown="1">

# Documentatie

## Vereisten

- PHP 8.1+ met extensies: `gd`, `json`, `openssl`, `mbstring`, `fileinfo`
- Apache met `mod_rewrite` (of PHP built-in server voor lokaal testen)
- Schrijfrechten op `data/` en `images/`

## Installatie

### Lokale ontwikkeling

```bash
git clone https://github.com/easeo-nl/easeo-cms.git
cd easeo-cms
php -S localhost:8000 router.php
```

Bezoek `http://localhost:8000` — de setup wizard begeleidt je door de configuratie.

### Productie (shared hosting)

1. Upload de bestanden naar je webserver (FTP of git deploy)
2. Bezoek het domein — `install.php` maakt de data bestanden aan
3. Doorloop de 5-stappen setup wizard
4. Configureer SMTP in admin voor e-mail notificaties

## Architectuur

Alle data wordt opgeslagen als JSON-bestanden in `data/`. Geen database, geen migraties, geen ORM.

| Bestand | Doel |
|---------|------|
| `site.json` | Bedrijfsinfo, kleuren, fonts, socials, tracking |
| `content.json` | Pagina-content per slug |
| `navigation.json` | Hoofd- en footer-menu items |
| `users.json` | Gebruikersaccounts |
| `posts.json` | Blogposts |
| `media.json` | Media bibliotheek index |
| `forms.json` | Formulierdefinities |
| `pages.json` | Dynamische pagina's |
| `redirects.json` | URL redirects |

### Core bestanden

- `includes/content.php` — Data layer: `load_json()`, `save_json()`, `site()`, `e()`
- `includes/lang.php` — i18n: `t()` vertaalfunctie
- `includes/header.php` — HTML head, navigatie, structured data
- `includes/footer.php` — Footer, cookie consent
- `includes/blog-engine.php` — Blog CRUD, paginering, post cards
- `includes/form-engine.php` — Formulier rendering, CSRF
- `includes/media-engine.php` — Upload, resize, thumbnails, SVG sanitization
- `includes/mailer.php` — SMTP via PHPMailer of `mail()` fallback

## Internationalisering (i18n)

EASEO CMS heeft ingebouwde meertalige ondersteuning. Taalbestanden staan in `lang/`:

```
lang/
  en.json    ← standaard (Engels)
  nl.json    ← Nederlands
```

Gebruik `t('key')` overal in PHP voor een vertaalde string:

```php
<?= t('button_save') ?>          // "Save" of "Opslaan"
<?= t('error_field_required', ['field' => 'Email']) ?>  // met parameters
```

### Een taal toevoegen

1. Kopieer `lang/en.json` naar `lang/xx.json` (je taalcode)
2. Vertaal alle waarden (houd de keys in het Engels)
3. Zet `language` in `data/site.json` op je taalcode

## Beveiliging

- **Authenticatie**: bcrypt wachtwoorden, secure session cookies (httponly, secure, samesite=strict)
- **2FA**: Optioneel per account, 6-cijferige e-mail codes, 10 min geldig
- **CSRF**: Tokens op alle POST requests
- **Rate limiting**: IP-based (5 pogingen/15 min) + account lockout (10 pogingen/15 min)
- **File uploads**: Extension whitelist, MIME check, SVG sanitization
- **Headers**: CSP, HSTS, X-Frame-Options, Permissions-Policy

## Data Soevereiniteit

EASEO CMS is ontworpen voor volledige controle over je data:

- **Geen cloud dependencies** — draait op elke server met PHP
- **Geen externe API's** — alle functionaliteit is self-contained
- **Geen telemetrie** — nul data naar derden
- **GDPR compliant** — geen tracking tenzij bewust ingeschakeld
- **EU-ontwikkeld** — Amsterdam, Nederland

Je JSON data bestanden zijn plain text. Je kunt ze lezen, exporteren, back-uppen en migreren met standaard tools. Geen proprietary formaat, geen vendor lock-in.

## Bijdragen

Pull requests zijn welkom. Fork het repository, maak je wijzigingen, en open een PR.

### Conventies

- PHP variabelen/functies: `snake_case`
- JSON keys: `snake_case`
- URL slugs: `kebab-case`
- Admin UI taal: bepaald door `lang/*.json`
- Commit messages: Engels, kort en beschrijvend

## Licentie

MIT License — zie [LICENSE.txt](https://github.com/easeo-nl/easeo-cms/blob/main/LICENSE.txt)

</div>
</section>
