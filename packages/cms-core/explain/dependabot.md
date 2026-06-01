# Dependabot

## Wat doet Dependabot?

Dependabot controleert automatisch of dependencies (`composer.json`, `package.json`)
verouderd zijn en opent Pull Requests om ze bij te werken.

In de EASEO-skeleton is Dependabot geconfigureerd voor Composer-dependencies met een
wekelijkse check.

## Configuratie (`.github/dependabot.yml`)

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    labels:
      - "dependabot"
    open-pull-requests-limit: 5
```

## Hoe een Dependabot PR te mergen

1. Bekijk de PR — Dependabot voegt automatisch release-notes toe.
2. Controleer of CI groen is (PHPUnit + lint).
3. Als CI groen: merge via GitHub-UI of:
   ```bash
   gh pr merge <PR-nummer> --merge
   ```
4. Dependabot bumpt automatisch andere open PRs na een merge.

## Bekijk open Dependabot PRs

```bash
gh pr list --label dependabot --state open
```

## Wanneer NIET direct mergen

- **Major version bump** (bijv. `0.x -> 1.0`): lees de CHANGELOG van het pakket
  eerst op breaking changes.
- **easeo/cms-core zelf**: coordinate met Nick — cms-core updates kunnen
  migraties vereisen.
- **phpmailer/phpmailer**: maak eerst smoke-test van contactformulier.

## Auto-merge instellen (optioneel)

Voor patch-updates (bijv. `1.2.3 -> 1.2.4`) kun je auto-merge activeren:

```bash
gh pr list --label dependabot --state open | while read pr rest; do
  gh pr merge "$pr" --auto --merge
done
```

**Gebruik dit alleen als CI alle gevallen dekt.**

## Dependabot alerts (security)

Naast PRs geeft Dependabot ook security alerts voor bekende CVEs in dependencies.
Te vinden onder: GitHub repo → Security → Dependabot alerts.

Kritieke alerts altijd melden aan Nick, ook als CI nog groen is.
