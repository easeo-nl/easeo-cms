<?php
/**
 * EASEO CMS — Legal text templates with placeholder replacement
 * Uses legal.json with keys: privacy, voorwaarden, cookies
 */

function get_legal_text(string $type): string {
    $legal = load_json('legal.json');
    $text = $legal[$type]['content'] ?? '';

    if (!empty($text)) {
        return replace_legal_placeholders($text);
    }

    // Return default template
    return replace_legal_placeholders(get_default_legal($type));
}

function replace_legal_placeholders(string $text): string {
    $replacements = [
        '{bedrijfsnaam}' => site('company.name', '[Bedrijfsnaam]'),
        '{email}' => site('company.email', '[E-mailadres]'),
        '{telefoon}' => site('company.phone', '[Telefoonnummer]'),
        '{adres}' => site('company.address', '[Adres]'),
        '{postcode}' => site('company.postcode', '[Postcode]'),
        '{plaats}' => site('company.city', '[Plaats]'),
        '{kvk}' => site('company.kvk', '[KVK-nummer]'),
        '{btw}' => site('company.btw', '[BTW-nummer]'),
        '{website}' => (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '[Website]'),
        '{datum}' => date('d-m-Y'),
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

function get_default_legal(string $type): string {
    switch ($type) {
        case 'privacy':
            return <<<'EOT'
Privacyverklaring

{bedrijfsnaam}, gevestigd aan {adres}, {postcode} {plaats}, is verantwoordelijk voor de verwerking van persoonsgegevens zoals weergegeven in deze privacyverklaring.

Contactgegevens:
{website}
{adres}, {postcode} {plaats}
{telefoon}
{email}

Persoonsgegevens die wij verwerken:
{bedrijfsnaam} verwerkt uw persoonsgegevens doordat u gebruik maakt van onze diensten en/of omdat u deze zelf aan ons verstrekt. Hieronder vindt u een overzicht van de persoonsgegevens die wij verwerken:
- Voor- en achternaam
- E-mailadres
- Telefoonnummer
- Overige persoonsgegevens die u actief verstrekt in correspondentie en telefonisch

Bijzondere en/of gevoelige persoonsgegevens die wij verwerken:
Onze website en/of dienst heeft niet de intentie gegevens te verzamelen over websitebezoekers die jonger zijn dan 16 jaar.

Met welk doel en op basis van welke grondslag wij persoonsgegevens verwerken:
{bedrijfsnaam} verwerkt uw persoonsgegevens voor de volgende doelen:
- Het afhandelen van uw betaling
- U te kunnen bellen of e-mailen indien dit nodig is om onze dienstverlening uit te kunnen voeren
- U te informeren over wijzigingen van onze diensten en producten

Hoe lang we persoonsgegevens bewaren:
{bedrijfsnaam} bewaart uw persoonsgegevens niet langer dan strikt nodig is om de doelen te realiseren waarvoor uw gegevens worden verzameld.

Delen van persoonsgegevens met derden:
{bedrijfsnaam} verstrekt uitsluitend aan derden en alleen als dit nodig is voor de uitvoering van onze overeenkomst met u of om te voldoen aan een wettelijke verplichting.

Cookies:
{bedrijfsnaam} gebruikt alleen technische en functionele cookies, en analytische cookies die geen inbreuk maken op uw privacy. Meer informatie vindt u op ons cookiebeleid.

Gegevens inzien, aanpassen of verwijderen:
U heeft het recht om uw persoonsgegevens in te zien, te corrigeren of te verwijderen. Daarnaast heeft u het recht om eventuele toestemming voor de gegevensverwerking in te trekken of bezwaar te maken tegen de verwerking. U kunt een verzoek tot inzage, correctie, verwijdering sturen naar {email}.

Hoe wij persoonsgegevens beveiligen:
{bedrijfsnaam} neemt de bescherming van uw gegevens serieus en neemt passende maatregelen om misbruik, verlies, onbevoegde toegang, ongewenste openbaarmaking en ongeoorloofde wijziging tegen te gaan.

KVK-nummer: {kvk}
BTW-nummer: {btw}
EOT;

        case 'voorwaarden':
            return <<<'EOT'
Algemene Voorwaarden — {bedrijfsnaam}

Artikel 1 — Definities
In deze voorwaarden wordt verstaan onder:
1. {bedrijfsnaam}: de ondernemer, gevestigd aan {adres}, {postcode} {plaats}, KVK: {kvk}.
2. Klant: de natuurlijke persoon of rechtspersoon die met {bedrijfsnaam} een overeenkomst aangaat.
3. Overeenkomst: elke afspraak of overeenkomst tussen {bedrijfsnaam} en de klant.

Artikel 2 — Toepasselijkheid
1. Deze algemene voorwaarden zijn van toepassing op elk aanbod van {bedrijfsnaam} en op elke tot stand gekomen overeenkomst.
2. Door gebruik te maken van de diensten van {bedrijfsnaam} gaat u akkoord met deze voorwaarden.

Artikel 3 — Aanbod en prijzen
1. Alle aanbiedingen zijn vrijblijvend tenzij anders vermeld.
2. Prijzen zijn inclusief BTW tenzij anders aangegeven.

Artikel 4 — Uitvoering
{bedrijfsnaam} zal de overeenkomst naar beste inzicht en vermogen uitvoeren.

Artikel 5 — Aansprakelijkheid
De aansprakelijkheid van {bedrijfsnaam} is beperkt tot het bedrag dat door de verzekering wordt gedekt.

Artikel 6 — Klachten
Klachten dienen binnen 14 dagen na ontdekking schriftelijk gemeld te worden aan {email}.

Artikel 7 — Toepasselijk recht
Op alle overeenkomsten is uitsluitend Nederlands recht van toepassing.

Contactgegevens:
{bedrijfsnaam}
{adres}, {postcode} {plaats}
{email} | {telefoon}
EOT;

        case 'cookies':
            return <<<'EOT'
Cookiebeleid — {bedrijfsnaam}

Wat zijn cookies?
Cookies zijn kleine tekstbestanden die op uw computer of mobiel apparaat worden opgeslagen wanneer u onze website bezoekt. Ze worden veel gebruikt om websites te laten werken en informatie te verstrekken aan de eigenaren van de website.

Welke cookies gebruiken wij?

Functionele cookies:
Deze cookies zijn noodzakelijk voor het functioneren van de website. Ze onthouden bijvoorbeeld uw cookievoorkeuren.

Analytische cookies:
Wij gebruiken analytische cookies om inzicht te krijgen in hoe bezoekers onze website gebruiken. Wij gebruiken deze informatie om onze website te verbeteren.

Marketing cookies:
Met uw toestemming plaatsen wij marketing cookies om relevante advertenties te tonen en de effectiviteit van campagnes te meten.

Cookies beheren:
U kunt uw cookievoorkeuren op elk moment wijzigen door onderaan de pagina op "Cookie-instellingen" te klikken. U kunt ook cookies verwijderen via uw browserinstellingen.

Contact:
Heeft u vragen over ons cookiebeleid? Neem contact op via {email}.

Laatste update: {datum}
EOT;

        default:
            return '';
    }
}
