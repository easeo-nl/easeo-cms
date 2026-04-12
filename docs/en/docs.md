---
layout: default
title: Documentation
description: Installation guide, architecture overview, and contribution guidelines for EASEO CMS.
lang: en
---

<section class="docs-content">
<div class="container" markdown="1">

# Documentation

## Requirements

- PHP 8.1+ with extensions: `gd`, `json`, `openssl`, `mbstring`, `fileinfo`
- Apache with `mod_rewrite` (or PHP built-in server for local testing)
- Write permissions on `data/` and `images/`

## Installation

### Local development

```bash
git clone https://github.com/easeo-nl/easeo-cms.git
cd easeo-cms
php -S localhost:8000 router.php
```

Visit `http://localhost:8000` — the setup wizard will guide you through configuration.

### Production (shared hosting)

1. Upload the files to your web server (FTP or git deploy)
2. Visit the domain — `install.php` creates the data files
3. Complete the 5-step setup wizard
4. Configure SMTP in admin for email notifications

## Architecture

All data is stored as JSON files in `data/`. No database, no migrations, no ORM.

| File | Purpose |
|------|---------|
| `site.json` | Company info, colors, fonts, socials, tracking |
| `content.json` | Page content keyed by slug |
| `navigation.json` | Main + footer menu items |
| `users.json` | User accounts |
| `posts.json` | Blog posts |
| `media.json` | Media library index |
| `forms.json` | Form definitions |
| `pages.json` | Dynamic pages |
| `redirects.json` | URL redirects |

### Core files

- `includes/content.php` — Data layer: `load_json()`, `save_json()`, `site()`, `e()`
- `includes/lang.php` — i18n: `t()` translation function
- `includes/header.php` — HTML head, navigation, structured data
- `includes/footer.php` — Footer, cookie consent
- `includes/blog-engine.php` — Blog CRUD, pagination, post cards
- `includes/form-engine.php` — Form rendering, CSRF
- `includes/media-engine.php` — Upload, resize, thumbnails, SVG sanitization
- `includes/mailer.php` — SMTP via PHPMailer or `mail()` fallback

## Internationalization (i18n)

EASEO CMS has built-in multilingual support. Language files are stored in `lang/`:

```
lang/
  en.json    ← default (English)
  nl.json    ← Dutch
```

Use `t('key')` anywhere in PHP to output a translated string:

```php
<?= t('button_save') ?>          // "Save" or "Opslaan"
<?= t('error_field_required', ['field' => 'Email']) ?>  // with params
```

### Adding a language

1. Copy `lang/en.json` to `lang/xx.json` (your locale code)
2. Translate all values (keep the keys in English)
3. Set `language` in `data/site.json` to your locale code

## Security

- **Authentication**: bcrypt passwords, secure session cookies (httponly, secure, samesite=strict)
- **2FA**: Optional per account, 6-digit email codes, 10 min validity
- **CSRF**: Tokens on all POST requests
- **Rate limiting**: IP-based (5 attempts/15 min) + account lockout (10 attempts/15 min)
- **File uploads**: Extension whitelist, MIME check, SVG sanitization
- **Headers**: CSP, HSTS, X-Frame-Options, Permissions-Policy

## Data Sovereignty

EASEO CMS is designed for full control over your data:

- **No cloud dependencies** — runs on any server with PHP
- **No external APIs** — all functionality is self-contained
- **No telemetry** — zero data sent to third parties
- **GDPR compliant** — no tracking unless explicitly enabled
- **EU-developed** — Amsterdam, Netherlands

Your JSON data files are plain text. You can read, export, backup, and migrate them with standard tools. No proprietary format, no vendor lock-in.

## Contributing

Pull requests are welcome. Fork the repository, make your changes, and open a PR.

### Conventions

- PHP variables/functions: `snake_case`
- JSON keys: `snake_case`
- URL slugs: `kebab-case`
- Admin UI language: determined by `lang/*.json`
- Commit messages: English, short and descriptive

## License

MIT License — see [LICENSE.txt](https://github.com/easeo-nl/easeo-cms/blob/main/LICENSE.txt)

</div>
</section>
