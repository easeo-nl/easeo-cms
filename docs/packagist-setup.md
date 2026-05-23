# Packagist + mirror-repo setup voor easeo/cms-core

> Eenmalig per package. Geldt nu voor `cms-core`; herhaal voor `shop-module` en `hello-module` als die later publiek gaan.

## Stap 1: Maak mirror-repo aan

Mirror-repo's zijn read-only — alleen splitsh/lite pushed ernaar. Naam-conventie: `easeo-nl/<package-naam-zonder-vendor>`.

```bash
gh repo create easeo-nl/cms-core --public \
  --description "READ-ONLY mirror van easeo-nl/easeo-cms packages/cms-core/. Issues + PRs in de monorepo." \
  --homepage "https://github.com/easeo-nl/easeo-cms"
```

In GitHub UI:
- Settings → General → Default branch: `main`
- Settings → Branches → Branch protection rule voor `main`: alleen `nick-aldewereld` mag pushen (write zonder PR vereist voor splitsh-action), maar regelarchitectuur: **niemand handmatig committen**

## Stap 2: README op mirror-repo

Push een minimale README zodat duidelijk is dat deze repo niet bedoeld is voor PRs:

```bash
cd /tmp && git clone git@github.com:easeo-nl/cms-core.git
cd cms-core
cat > README.md <<'EOF'
# easeo/cms-core (mirror)

Dit is een **read-only mirror** van [`easeo-nl/easeo-cms`](https://github.com/easeo-nl/easeo-cms) — specifiek de directory `packages/cms-core/`. Deze repo is alleen het distributie-kanaal voor Composer/Packagist.

## Issues + Pull Requests

Open ze in de monorepo: https://github.com/easeo-nl/easeo-cms/issues

## Installatie

\`\`\`bash
composer require easeo/cms-core
\`\`\`

Zie de monorepo README voor documentatie.
EOF
git add README.md
git commit -m "Initial mirror README"
git push origin main
cd .. && rm -rf cms-core
```

## Stap 3: Packagist-account + submit

1. Account: https://packagist.org/ → log in met `easeo-nl` GitHub org (of persoonlijk account met org-toegang)
2. Submit: https://packagist.org/packages/submit → paste `https://github.com/easeo-nl/cms-core`
3. Verifieer dat package-naam `easeo/cms-core` is (uit composer.json van packages/cms-core/)
4. Maintainers: voeg `nick-aldewereld` toe als maintainer

## Stap 4: Auto-update webhook

Packagist haalt updates uit GitHub:

1. Op Packagist: My Packages → `easeo/cms-core` → Settings → kopieer de **API token** en jouw username
2. Op de mirror-repo GitHub: Settings → Webhooks → Add webhook:
   - Payload URL: `https://packagist.org/api/github?username=<jouw-packagist-username>`
   - Content type: `application/json`
   - Secret: `<packagist-api-token>`
   - Events: alleen `push`
3. Test: push een lege commit naar de mirror → Packagist → My Packages → "Last update" updates binnen 30s.

## Stap 5: Secrets in monorepo

Voor release.yml om handmatig Packagist te triggeren (fallback als webhook faalt):

```bash
gh secret set PACKAGIST_API_TOKEN -R easeo-nl/easeo-cms < <(echo "<token>")
gh secret set PACKAGIST_USERNAME -R easeo-nl/easeo-cms < <(echo "<username>")
```

Plus de mirror deploy-key (voor splitsh/lite push):

```bash
ssh-keygen -t ed25519 -C "splitsh-deploy@cms-core" -f /tmp/mirror_cms_core -N ""

# Pubkey naar mirror-repo als deploy key met write-toegang:
gh repo deploy-key add /tmp/mirror_cms_core.pub \
  -R easeo-nl/cms-core \
  -t "splitsh deploy key" \
  -w

# Private key als secret in monorepo:
gh secret set MIRROR_CMS_CORE_DEPLOY_KEY \
  -R easeo-nl/easeo-cms \
  < /tmp/mirror_cms_core

rm /tmp/mirror_cms_core /tmp/mirror_cms_core.pub
```

## Verificatie

- https://packagist.org/packages/easeo/cms-core toont package met versies
- `composer search easeo/cms-core` (in een willekeurige composer-project) vindt 'm
- `composer require easeo/cms-core:^0.1` werkt in een test-dir

## Na deze setup

- Plan 05 A9 kan geïmplementeerd worden (release workflow yml)
- Plan 05 A10 kan getest worden (eerste tag → end-to-end pipeline)
- Plan 05 A11 (branch-protection) is een aparte handmatige stap, zie docs/branch-protection.md
