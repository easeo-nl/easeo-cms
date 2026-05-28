# Plan 11 — qpmarketing-website cutover + `apps/qpmarketing-website` referentie-app (Fase G)

> **Status:** Skeleton. Volledige TDD-steps worden ingevuld wanneer Plan 10 (rww cutover) ≥1 week stabiel draait.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Migreer `qpmarketing-website` (klant-repo voor qpmarketing.nl) naar thin-site-app stack (zelfde pattern als Plan 10), en kopieer een geanonymiseerde versie als `apps/qpmarketing-website` in de monorepo als **referentie-implementatie** (genesis-strategie: ergens beginnen, in volgende iteraties optimaliseren).

**Architectuur:**
- Klant-repo `easeo-nl/qpmarketing-website` ondergaat dezelfde cutover als rww (Plan 10 pattern).
- Aanvullend: directory-naming-normalisatie van `qp-beheer/` naar `beheer/` (cms-core verwacht laatste). Eenmalige rename als onderdeel van cutover.
- Aanvullend: deploy-pipeline opzetten **vanaf nul** (qpmarketing had geen GitHub Actions, alleen rsync deploy.sh). Plan 06 deploy.yml template gebruiken.
- Referentie-app: kopie van klant-repo in `easeo-nl/easeo-cms/apps/qpmarketing-website/` als immutable snapshot, met klantspecifieke data verwijderd/vervangen door demo-fixtures. Doel: tweede live-voorbeeld voor toekomstige onboarding van klanten/devs. Update-cadens: handmatig, alleen als pattern fundamenteel wijzigt.

**Tech Stack:** Identiek aan Plan 10.

**Afhankelijkheden:** Plan 05-10 afgerond, Plan 10 ≥1 week stabiel op rwwbouw.nl, Plan 09 task E6 server-prep voor qpmarketing.nl gedaan.

**Afbakening:** Géén feature-changes. Alleen infrastructuur. `apps/qpmarketing-website/` referentie-app is bewust een momentopname; geen sync-mechanisme naar productie-repo (zou complexity toevoegen die we niet nodig hebben — KISS, herzien in latere iteratie als nodig).

---

## Bestandsstructuur

**Op `easeo-nl/qpmarketing-website` repo (force-push na backup-tag):** identiek aan Plan 10 file-structuur, met QP-specifieke branding/templates/secrets.

**In monorepo `easeo-nl/easeo-cms`:**
- `apps/qpmarketing-website/` — gekopieerde versie van klant-repo
  - `composer.json` — gebruikt path-repo naar `packages/*` (ontwikkel-modus, niet productie)
  - Identieke directory-structuur als klant-repo
  - `data/` met **demo-fixtures** (niet de productie-data — geanonymiseerd)
  - `README.md` met disclaimer: "Dit is een referentie-snapshot van qpmarketing-website voor onboarding en pattern-documentatie. NIET de live productie-versie."

**Verwijderd:**
- Op klant-repo: `cms/`-directory (door deploy.sh al gemarkeerd als dead code)
- Op klant-repo: `deploy.sh`, `.env.deploy.example` (vervangen door GitHub Actions deploy.yml)
- Op klant-repo: `qp-beheer/` (hernoemt naar `beheer/`, maar bij thin-site-app komt beheer-UI uit cms-core dus deze dir verdwijnt grotendeels — alleen QP-specifieke overrides blijven in `templates/`)

---

## Tasks-outline

### Task G1: Communicatie + window-planning
Met Ardo/Sylvester schedulen. QP heeft géén GitHub Actions yet (huidige deploy is rsync van Nick's laptop) — zelfde frozen-window aanpak als rww.

### Task G2: Pre-cutover backup (klant-repo + server)
Klant-repo: `git tag pre-cutover-snapshot && git push --tags`. Server: SSH tarball van shared/.

### Task G3: Audit `qp-beheer/` → `beheer/` rename impact
Grep huidige qpmarketing-website voor hardcoded references naar `qp-beheer/`. Document elke vindplek. Plan rename als onderdeel van cutover (in nieuwe code-base zit alleen `beheer/` via cms-core; oude `qp-beheer/` verdwijnt).

### Task G4: Genereer nieuwe klant-repo inhoud
`php tools/instantiate-skeleton.php --site=qpmarketing --domain=qpmarketing.nl --reviewer=<ardo-handle> --backstop=<nick-handle> --output=/tmp/qpmarketing-bootstrap/`.

### Task G5: Branding/templates port
QP heeft eigen huisstijl (Outfit + Inter fonts, eigen kleuren — zie huidige `css/custom.css` + `CLAUDE.md`). Port naar `templates/`-overrides en `public/assets/css/`.

### Task G6: Force-push klant-repo
Identiek aan Plan 10 F6.

### Task G7: GitHub Secrets + deploy.yml
Verschil met rww: deploy.yml moet nu volledig nieuw, geen voorganger. Gebruik Plan 06 skeleton-template, vul placeholders in. Generate dedicated deploy-key voor qpmarketing.

### Task G8: Eerste deploy via Actions
Watch end-to-end. Verwachte hick-ups: oude file-paths in browser-cache (instrueer Ardo+Sylvester voor hard-refresh test).

### Task G9: Live verify
Open https://qpmarketing.nl/. Visueel identiek, Quick-Scan form werkt, admin werkt op `/beheer/`. Annotation in UptimeRobot.

### Task G10: 48u observatie

### Task G11: Legacy-cleanup + hand-off

### Task G12: Genereer `apps/qpmarketing-website/` in monorepo
Kopieer klant-repo (na cutover succesvol) naar `apps/qpmarketing-website/`. Anonymiseer `data/`: verwijder echte gebruikers (`users.json` krijgt dummy `demo-admin`/`demo-pw`), echte form-submissions weg, behoud structuur van content. composer.json switchen naar path-repo voor monorepo-context.

### Task G13: CI smoke uitbreiden naar qpmarketing-app
Voeg aan `.github/workflows/ci.yml` smoke-job (Plan 05 A6) een tweede iteratie toe die `apps/qpmarketing-website/` start (port 8081) en de QP-specifieke routes curlt. Verzekert dat core-changes niet stil iets in QP-stijl breken.

### Task G14: README in monorepo bijwerken
"Site-apps in deze monorepo: `apps/easeo-website` (productie easeo.nl), `apps/qpmarketing-website` (referentie/snapshot van qpmarketing.nl), `apps/_skeleton` (template voor nieuwe sites), `apps/_fixture-app` (CI-only)".

---

## Plan-status

| Task | Status |
|---|---|
| G1 — communicatie + window | ☐ |
| G2 — pre-cutover backup | ☐ |
| G3 — qp-beheer rename audit | ☐ |
| G4 — genereer nieuwe inhoud | ☐ |
| G5 — branding/templates port | ☐ |
| G6 — force-push klant-repo | ☐ |
| G7 — Secrets + deploy.yml | ☐ |
| G8 — eerste deploy | ☐ |
| G9 — live verify | ☐ |
| G10 — 48u observatie | ☐ |
| G11 — legacy-cleanup + hand-off | ☐ |
| G12 — apps/qpmarketing-website in monorepo | ☐ |
| G13 — CI smoke voor qp-app | ☐ |
| G14 — monorepo README update | ☐ |
