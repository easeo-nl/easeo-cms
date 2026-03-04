# Claude Code Opdracht: Contextual Help & Welkomstscherm

## Context

Het EASEO CMS heeft een werkend admin panel in `beheer/`. De gebruikers zijn MKB-ondernemers die een paar keer per maand inloggen. Ze lezen geen documentatie. Alle uitleg moet IN het panel zitten, op de plek waar de vraag ontstaat.

Twee toevoegingen:
1. Contextual help tooltips bij formuliervelden
2. Eenmalig welkomstscherm na setup

---

## 1. Tooltip systeem

### Hoe het werkt

Een klein vraagteken-icoontje (?) naast velden die uitleg nodig hebben. Bij klik/hover verschijnt een korte tekst. Geen externe library — puur CSS + minimal vanilla JS.

### HTML structuur

```html
<label>
  SEO Titel
  <span class="help-tooltip" data-help="Maximaal 60 tekens. Dit is wat Google toont als paginatitel in de zoekresultaten.">?</span>
</label>
```

### CSS (toevoegen aan beheer/assets/admin.css)

```css
.help-tooltip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #334155;
  color: #94A3B8;
  font-size: 11px;
  font-weight: 600;
  cursor: help;
  margin-left: 6px;
  position: relative;
  vertical-align: middle;
  transition: background 0.15s;
}

.help-tooltip:hover {
  background: #2563eb;
  color: #fff;
}

.help-tooltip .help-text {
  display: none;
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  background: #1e293b;
  color: #e2e8f0;
  font-size: 12px;
  font-weight: 400;
  line-height: 1.5;
  padding: 10px 14px;
  border-radius: 6px;
  width: 260px;
  max-width: 80vw;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 100;
  pointer-events: none;
}

.help-tooltip .help-text::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: #1e293b;
}

.help-tooltip.active .help-text {
  display: block;
}
```

### JavaScript (toevoegen aan beheer/assets/admin.js of inline)

```javascript
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.help-tooltip').forEach(function(el) {
    // Maak help-text element aan vanuit data-help attribuut
    var text = document.createElement('span');
    text.className = 'help-text';
    text.textContent = el.getAttribute('data-help');
    el.appendChild(text);
    
    // Toggle bij klik (mobiel-vriendelijk)
    el.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      // Sluit andere tooltips
      document.querySelectorAll('.help-tooltip.active').forEach(function(other) {
        if (other !== el) other.classList.remove('active');
      });
      el.classList.toggle('active');
    });
  });
  
  // Sluit tooltip bij klik ergens anders
  document.addEventListener('click', function() {
    document.querySelectorAll('.help-tooltip.active').forEach(function(el) {
      el.classList.remove('active');
    });
  });
});
```

### Waar tooltips moeten komen

Doorloop ALLE admin pagina's in `beheer/pages/` en voeg tooltips toe bij de volgende velden. Gebruik EXACT deze teksten:

#### Content editor (content.php)
- SEO titel: "Maximaal 60 tekens. Dit is wat Google toont als paginatitel in de zoekresultaten."
- SEO omschrijving: "Maximaal 155 tekens. De korte beschrijving onder de paginatitel in Google."
- Velden met `_image` suffix: "Klik om een afbeelding te kiezen uit de mediabibliotheek."

#### Blog editor (blog.php)
- Titel: "De titel van je artikel. Wordt ook gebruikt als SEO titel als je die leeg laat."
- Slug: "Het webadres van dit artikel. Wordt automatisch aangemaakt vanuit de titel. Pas alleen aan als je een goede reden hebt."
- Excerpt: "Een korte samenvatting die getoond wordt op de overzichtspagina. 1–2 zinnen is genoeg."
- Content: "Schrijf in HTML. Gebruik <p> voor alinea's, <h2> voor tussenkopjes, <strong> voor vet, <a href=\"...\"> voor links."
- Categorie: "Categorieën kun je beheren via de bloginstellingen."
- Afbeelding: "De uitgelichte afbeelding bij dit artikel. Wordt getoond op de overzichtspagina en bovenaan het artikel."
- Status: "Concept-artikelen zijn alleen zichtbaar in het beheerpanel. Gepubliceerd is zichtbaar voor bezoekers."
- SEO titel: "Maximaal 60 tekens. Laat leeg om de artikeltitel te gebruiken."
- SEO omschrijving: "Maximaal 155 tekens. Laat leeg om het excerpt te gebruiken."

#### Formulieren (formulieren.php)
- Formulier naam: "Alleen voor intern gebruik. Bezoekers zien deze naam niet."
- E-mailadres: "Inzendingen van dit formulier worden naar dit adres gestuurd."
- Succesmelding: "De tekst die bezoekers zien na het versturen van het formulier."
- Veld type: "Tekst = korte invoer, Textarea = lang tekstveld, E-mail = met validatie, Selectie = dropdown menu."
- Verplicht: "Als dit aan staat, kan het formulier niet verstuurd worden zonder dit veld in te vullen."

#### Media (media.php)
- Alt tekst: "Beschrijf wat er op de afbeelding te zien is. Belangrijk voor Google en bezoekers die de afbeelding niet kunnen zien."
- Bij upload zone: "Sleep afbeeldingen hierheen of klik om te uploaden. Maximaal 5MB per bestand. JPG, PNG, WebP en SVG."

#### Navigatie (navigatie.php)
- Label: "De tekst die bezoekers zien in het menu."
- URL: "Het adres waar de link naartoe gaat. Kies een bestaande pagina of typ een extern adres."
- Highlight: "Gemarkeerde items worden als opvallende knop getoond, bijvoorbeeld voor een CTA."

#### Huisstijl (huisstijl.php)
- Primaire kleur: "De hoofdkleur van knoppen, links en accenten op de website."
- Secundaire kleur: "De kleur voor highlights, prijzen en call-to-action elementen."
- Donkere kleur: "De achtergrondkleur van de header en footer."
- Lettertype display: "Het lettertype voor koppen en titels."
- Lettertype body: "Het lettertype voor lopende tekst en menu-items."

#### Tracking (tracking.php)
- GTM ID: "Google Tag Manager container-ID. Begint met GTM-. Hiermee kun je alle tracking centraal beheren."
- GA4 ID: "Google Analytics 4 tracking-ID. Begint met G-. Voor websitestatistieken."
- Search Console: "De verificatiecode van Google Search Console. Alleen de code, niet de hele meta-tag."
- Google Ads Conversie-ID: "Voor het meten van conversies uit Google Ads campagnes."
- Facebook Pixel ID: "Voor het meten van websitebezoek vanuit Facebook en Instagram advertenties."
- Custom head code: "HTML of JavaScript die in de <head> van elke pagina wordt geplaatst. Alleen voor gevorderd gebruik."
- Custom body code: "HTML of JavaScript die vlak voor </body> wordt geplaatst. Alleen voor gevorderd gebruik."

#### Redirects (redirects.php)
- Oud adres: "Het oude webadres dat niet meer bestaat. Begint met /. Voorbeeld: /oude-pagina"
- Nieuw adres: "Het nieuwe webadres waar bezoekers naartoe gestuurd worden. Voorbeeld: /nieuwe-pagina"

#### Gebruikers (gebruikers.php)
- Rol: "Beheerder heeft volledige toegang. Redacteur kan content en blog beheren maar geen instellingen wijzigen."

#### Backup (backup.php)
- Bij download knop: "Download een kopie van alle content, instellingen en uploads. Bewaar deze op een veilige plek."
- Bij herstel/upload: "Herstel een eerdere backup. Let op: dit overschrijft alle huidige content en instellingen."

#### Juridisch (juridisch.php)
- Bij "Reset naar template": "Zet de tekst terug naar het standaard template met je bedrijfsgegevens ingevuld. Je huidige aanpassingen gaan verloren."

---

## 2. Welkomstscherm

### Hoe het werkt

Na het voltooien van de setup wizard wordt er een veld `"show_welcome": true` opgeslagen in `data/site.json`. Bij het eerste bezoek aan het dashboard toont het admin panel een welkomstscherm. Na klik op "Aan de slag" of "Niet meer tonen" wordt `show_welcome` op `false` gezet.

### Aanpassing in setup.php

Voeg `"show_welcome": true` toe aan het site.json bestand dat door de wizard wordt aangemaakt — op hetzelfde niveau als `setup_complete`.

### Welkomstscherm in dashboard (beheer/pages/dashboard.php)

Voeg bovenaan de dashboard-pagina toe, VOOR de bestaande dashboard content:

```php
<?php
$site = json_decode(file_get_contents(__DIR__ . '/../../data/site.json'), true);
if (!empty($site['show_welcome'])): 
?>
<div id="welcome-screen" style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:40px;margin-bottom:32px;">
  
  <h2 style="color:#f8fafc;font-size:22px;font-weight:700;margin:0 0 8px 0;">
    Welkom bij je nieuwe website
  </h2>
  <p style="color:#94a3b8;font-size:14px;margin:0 0 32px 0;">
    Alles is klaar. Hier zijn de drie dingen die je het vaakst gaat doen:
  </p>
  
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;">
    
    <!-- Blogpost -->
    <a href="?page=blog&action=new" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">✍️</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Blogpost schrijven</div>
      <div style="color:#64748b;font-size:13px;">Schrijf een nieuw artikel voor je website.</div>
    </a>
    
    <!-- Content aanpassen -->
    <a href="?page=content" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">📝</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Tekst aanpassen</div>
      <div style="color:#64748b;font-size:13px;">Wijzig teksten, titels en afbeeldingen op je pagina's.</div>
    </a>
    
    <!-- Afbeelding uploaden -->
    <a href="?page=media" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">📸</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Afbeelding uploaden</div>
      <div style="color:#64748b;font-size:13px;">Voeg foto's toe aan je mediabibliotheek.</div>
    </a>
    
  </div>
  
  <button onclick="dismissWelcome()" style="background:#334155;color:#94a3b8;border:none;padding:10px 20px;border-radius:6px;font-size:13px;cursor:pointer;font-family:inherit;">
    Niet meer tonen
  </button>

</div>

<script>
function dismissWelcome() {
  fetch('?action=dismiss_welcome', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'} })
    .then(function() {
      document.getElementById('welcome-screen').style.display = 'none';
    });
}
</script>
<?php endif; ?>
```

### Dismiss endpoint

Voeg een handler toe (in de dashboard pagina of in de admin router) die bij POST naar `?action=dismiss_welcome` het volgende doet:

```php
if (isset($_GET['action']) && $_GET['action'] === 'dismiss_welcome' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_path = __DIR__ . '/../../data/site.json';
    $site = json_decode(file_get_contents($site_path), true);
    $site['show_welcome'] = false;
    file_put_contents($site_path, json_encode($site, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    http_response_code(200);
    exit;
}
```

### Aanpassing in site.template.json

Voeg `"show_welcome": true` toe aan de template zodat het bij elke nieuwe installatie actief is.

---

## 3. Volgorde

1. Maak het tooltip CSS en JS systeem (global, in admin assets)
2. Doorloop ALLE admin pagina's en voeg tooltips toe met de exacte teksten hierboven
3. Voeg `show_welcome` toe aan setup.php en site.template.json
4. Bouw het welkomstscherm in dashboard.php
5. Bouw de dismiss handler
6. Test: `php -S localhost:8000`, doorloop setup, check welkomstscherm, check tooltips op elke pagina

## 4. Controleer na afloop

- [ ] Tooltip CSS en JS geladen op alle admin pagina's
- [ ] Tooltips zichtbaar en werkend op: content, blog, formulieren, media, navigatie, huisstijl, tracking, redirects, gebruikers, backup, juridisch
- [ ] Tooltips sluiten bij klik erbuiten
- [ ] Tooltips werken op mobiel (klik, niet hover)
- [ ] Welkomstscherm verschijnt na setup wizard
- [ ] Drie kaarten linken naar juiste admin pagina's
- [ ] "Niet meer tonen" werkt en welkomstscherm verdwijnt permanent
- [ ] `show_welcome` staat op `false` in site.json na dismiss
- [ ] Bij volgende login verschijnt welkomstscherm niet meer
- [ ] Geen JavaScript errors in console
