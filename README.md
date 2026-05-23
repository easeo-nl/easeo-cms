# EASEO CMS

[![CI](https://github.com/easeo-nl/easeo-cms/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/easeo-nl/easeo-cms/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/easeo/cms-core?label=easeo%2Fcms-core)](https://packagist.org/packages/easeo/cms-core)
[![License](https://img.shields.io/packagist/l/easeo/cms-core)](LICENSE.txt)

A lightweight, open-source PHP CMS built as an alternative to WordPress. No database, no frameworks, no dependencies — runs on any shared hosting with PHP 8.x.

**[Website](https://easeo-nl.github.io/easeo-cms)** · **[Documentation](https://easeo-nl.github.io/easeo-cms/en/docs)** · **[Nederlands](https://easeo-nl.github.io/easeo-cms/nl/)**

## Why EASEO CMS?

WordPress is overkill for most small business websites. It's slow, vulnerable, and requires constant updates of plugins and themes. EASEO CMS is built from practice: a CMS that does what it should — nothing more.

- **Zero dependencies** — no Composer, no npm, no build tools
- **JSON instead of MySQL** — no database needed, works everywhere
- **Secure by design** — 2FA, CSRF, rate limiting, CSP headers, bcrypt
- **Ready in 5 minutes** — upload, run the wizard, done
- **Multilingual** — built-in i18n with JSON language files

## Digital Sovereignty

Your data, your server, your rules:

- **No cloud dependencies** — no AWS, no Google Cloud, no Cloudflare required
- **No external APIs** — all functionality is self-contained
- **No telemetry** — zero data sent to third parties
- **GDPR by design** — no tracking unless explicitly enabled
- **Self-hosted** — runs on any European hosting provider
- **EU-developed** — Amsterdam, Netherlands

## Features

- Admin panel with dark theme and responsive design
- Blog engine with RSS feed and Schema.org structured data
- Dynamic pages with parent/child structure and menu integration
- Form builder with inbox, email notifications (SMTP) and CSV export
- SEO: auto-sitemap, JSON-LD, meta tags, breadcrumbs
- Security: 2FA, CSRF, bcrypt, rate limiting, account lockout, CSP headers
- Branding editor: colors, fonts and logo via admin
- Media library with upload, resize and thumbnails
- Backup, audit logging, cookie consent, tracking (GTM/GA4/Pixel)
- i18n: English and Dutch included, add any language with one file

## Requirements

- PHP 8.1+ with extensions: `gd`, `json`, `openssl`, `mbstring`, `fileinfo`
- Apache with `mod_rewrite` (or PHP built-in server for local testing)
- Write permissions on `data/` and `images/`

## Installation

```bash
git clone https://github.com/easeo-nl/easeo-cms.git
cd easeo-cms
php -S localhost:8000 router.php
```

Visit `http://localhost:8000` — the setup wizard guides you through configuration.

### Production (shared hosting)

1. Upload the files to your web server (FTP or git deploy)
2. Visit the domain — `install.php` creates the data files
3. Complete the 5-step setup wizard
4. Configure SMTP in admin for email notifications

## Architecture

```
index.php              Homepage
blog.php / blog-post.php   Blog
contact.php            Contact page
pagina-router.php      Dynamic pages

includes/              Core PHP (content, auth, forms, media, mail, i18n)
beheer/                Admin panel (dark theme)
templates/             Reusable frontend sections
lang/                  Language files (en.json, nl.json, ...)
data/                  JSON data files (git-ignored)
```

All data is stored as JSON in `data/`. No database setup, no migrations, no ORM.

## Contributing

Pull requests are welcome. Fork the repository, make your changes, and open a PR.

Conventions:
- PHP: `snake_case` for variables and functions
- JSON keys: `snake_case`
- URL slugs: `kebab-case`
- Commit messages: English, short and descriptive

## License

MIT License — see [LICENSE.txt](LICENSE.txt)

## Author

**Nick Aldewereld** — [EASEO](https://www.easeo.nl)

---

Built as an open-source alternative to the CMS monopolies. Use it, fork it, make it better.
