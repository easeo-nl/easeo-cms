# Plan 09 — Capistrano server-layout op Hostinger (Fase E)

> **Status:** Skeleton. Volledige stappen worden ingevuld bij start (server-state-detectie eerst, daarna concrete commands).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) of superpowers:executing-plans om dit plan task-by-task uit te voeren.

**Goal:** Restructureer de webroot van elke productie-Hostinger-account naar een Capistrano-style layout (`releases/`, `shared/`, `current` symlink). Voorbereiden voor zero-data-overschrijven deploys uit Plan 08, 10, 11.

**Architectuur:**
- Per Hostinger-account: maak `~/domains/<site>/{shared,releases}/`, verhuis huidige `public_html/data/` en `public_html/images/uploads/` naar `shared/`, symlink ze terug. Verplaats huidige flat layout naar `releases/legacy-YYYY-MM-DD/`. Apache `public_html/` wordt zelf een symlink naar `releases/<latest>/`.
- Apache vhost / `.htaccess` aanpassen zodat DocumentRoot binnen de release het juiste `public/` subpad raakt (in Plan 06 skeleton).
- Eenmalige handmatige stap per server; daarna doen deploy-pipelines de rest.

**Tech Stack:** SSH, bash, Hostinger shared/managed VPS (Apache 2.4 + .htaccess), géén root-toegang.

**Afhankelijkheden:** Géén voorgaande monorepo-plans noodzakelijk — kan parallel met Plan 06/07. Vóór Plan 08 (easeo.nl cutover): server-prep voor easeo.nl moet klaar zijn. Vóór Plan 10/11 (rww/qpmarketing): server-prep voor die accounts moet klaar zijn.

**Afbakening:** Géén code-changes in de monorepo. Volledig operationeel/devops. Resultaat: een werkbare layout per server, niets meer.

---

## Bestandsstructuur

**Aangemaakt (alleen runbooks, geen code):**
- `docs/runbooks/server-prep-hostinger.md` — generieke walkthrough per Hostinger-account
- `docs/runbooks/server-prep-rollback.md` — als prep faalt halverwege: hoe terug naar flat layout
- `docs/runbooks/per-site-prep-checklist.md` — invul-checklist (1 per site)
- `tools/check-server-layout.sh` — diagnostic script: SSH in, check Capistrano-layout intact, rapporteer drift

**Gewijzigd:** N.v.t.

---

## Tasks-outline

### Task E1: Server-prep runbook schrijven
Stap-voor-stap commands volgens spec sectie 7 ("Per klant — voorbereiding"). Test elke regel handmatig op een test-account vóór finaliseren (Hostinger sandbox of een test-Hostinger-account als beschikbaar; anders eerste echte run op easeo.nl met extra dubbel-check).

### Task E2: Rollback-runbook schrijven
Voor scenario: directory-move faalt halverwege (bv. permission denied op een specifieke file). Stap-voor-stap: stop, restore vanuit pre-prep backup, herstart.

### Task E3: Per-site invul-checklist
Template met velden: site-naam, Hostinger-username, domein, SSH-host:port, pre-prep backup-locatie, datum prep, datum eerste deploy, datum legacy-cleanup. Eén file per site, ingevuld als prep doorgaat.

### Task E4: Server-prep voor `easeo.nl`
Voer runbook uit. Voor: backup live data. Tijdens: layout-move. Na: verifieer dat oude site nog steeds rendert via tijdelijke .htaccess-rewrite (geen DocumentRoot-switch yet — die komt in Plan 08).

### Task E5: Server-prep voor `rwwbouw.nl`
Idem als E4 maar voor RWW. Vereist coördinatie met Ardo (notify dat er ~10 min "frozen" window is, geen CMS-edits in die periode).

### Task E6: Server-prep voor `qpmarketing.nl`
Idem voor QP. Extra: QP heeft `qp-beheer/` directory die naar `beheer/` hernoemt moet worden vóór Plan 11 — markeer als follow-up.

### Task E7: `tools/check-server-layout.sh` script
Bash script dat per site SSH't en checkt: `~/domains/<site>/{shared/data,shared/images/uploads,releases,current}` bestaan en kloppen. Output: `OK/DRIFT` per site. Te draaien voor en na elke deploy van Plan 08+.

### Task E8: Documentatie-update
Voeg een sectie "Server-layout" toe aan monorepo README met link naar runbooks. Voeg per-site checklists toe in een nieuwe `docs/sites/` directory (gitignored? of in git — beslissen: alléén toevoegen als geen credentials inhoudt; layout zelf is niet gevoelig).

---

## Plan-status

| Task | Status |
|---|---|
| E1 — server-prep runbook | ☐ |
| E2 — rollback-runbook | ☐ |
| E3 — per-site invul-checklist template | ☐ |
| E4 — server-prep easeo.nl | ☐ |
| E5 — server-prep rwwbouw.nl | ☐ |
| E6 — server-prep qpmarketing.nl | ☐ |
| E7 — check-server-layout.sh | ☐ |
| E8 — README + docs update | ☐ |
