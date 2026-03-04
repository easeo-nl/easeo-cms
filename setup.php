<?php
/**
 * EASEO CMS — Setup Wizard (first-run)
 * 5 steps: company info, branding, pages, admin account, confirmation
 */
require_once __DIR__ . '/includes/content.php';

// Redirect if already set up
if (is_setup_complete()) {
    header('Location: /');
    exit;
}

session_start();

$step = max(1, min(5, (int)($_GET['step'] ?? $_POST['step'] ?? 1)));
$errors = [];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step 1: Company info
    if ($action === 'step1') {
        $siteData = load_json('site.json');
        $siteData['company']['name'] = trim($_POST['bedrijfsnaam'] ?? '');
        $siteData['company']['tagline'] = trim($_POST['tagline'] ?? '');
        $siteData['company']['email'] = trim($_POST['email'] ?? '');
        $siteData['company']['phone'] = trim($_POST['telefoon'] ?? '');
        $siteData['company']['address'] = trim($_POST['adres'] ?? '');
        $siteData['company']['postcode'] = trim($_POST['postcode'] ?? '');
        $siteData['company']['city'] = trim($_POST['plaats'] ?? '');
        $siteData['company']['kvk'] = trim($_POST['kvk'] ?? '');
        $siteData['company']['btw'] = trim($_POST['btw'] ?? '');

        if (empty($siteData['company']['name'])) {
            $errors[] = 'Bedrijfsnaam is verplicht.';
        } elseif (empty($siteData['company']['email']) || !filter_var($siteData['company']['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Een geldig e-mailadres is verplicht.';
        } else {
            save_json('site.json', $siteData);
            header('Location: /setup.php?step=2');
            exit;
        }
    }

    // Step 2: Branding
    if ($action === 'step2') {
        $siteData = load_json('site.json');
        $siteData['brand']['color_primary'] = $_POST['color_primary'] ?? '#2563EB';
        $siteData['brand']['color_secondary'] = $_POST['color_secondary'] ?? '#EA580C';
        $siteData['brand']['color_dark'] = $_POST['color_dark'] ?? '#111827';
        $siteData['brand']['color_muted'] = $_POST['color_muted'] ?? '#9CA3AF';
        $siteData['brand']['font_display'] = $_POST['font_display'] ?? 'Outfit';
        $siteData['brand']['font_body'] = $_POST['font_body'] ?? 'Inter';
        save_json('site.json', $siteData);
        header('Location: /setup.php?step=3');
        exit;
    }

    // Step 3: Pages
    if ($action === 'step3') {
        $contentData = load_json('content.json');
        if (!isset($contentData['home'])) $contentData['home'] = [];
        $contentData['home']['hero_titel'] = trim($_POST['hero_titel'] ?? '');
        $contentData['home']['hero_tekst'] = trim($_POST['hero_tekst'] ?? '');
        if (!isset($contentData['over'])) $contentData['over'] = [];
        $contentData['over']['inhoud_tekst'] = trim($_POST['over_tekst'] ?? '');
        save_json('content.json', $contentData);
        header('Location: /setup.php?step=4');
        exit;
    }

    // Step 4: Admin account
    if ($action === 'step4') {
        $naam = trim($_POST['naam'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['wachtwoord'] ?? '';
        $password2 = $_POST['wachtwoord2'] ?? '';

        if (empty($naam)) {
            $errors[] = 'Naam is verplicht.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Een geldig e-mailadres is verplicht.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Wachtwoord moet minimaal 8 tekens bevatten.';
        } elseif ($password !== $password2) {
            $errors[] = 'Wachtwoorden komen niet overeen.';
        } else {
            $users = [
                [
                    'email' => $email,
                    'naam' => $naam,
                    'rol' => 'admin',
                    'wachtwoord' => password_hash($password, PASSWORD_DEFAULT),
                    'aangemaakt' => date('Y-m-d H:i:s'),
                ]
            ];
            save_json('users.json', ['users' => $users]);

            // Initialize data files with correct structures
            if (empty(load_json('forms.json')['forms'] ?? [])) {
                save_json('forms.json', ['forms' => [
                    [
                        'id' => 'contact',
                        'naam' => 'Contactformulier',
                        'velden' => [
                            ['naam' => 'naam', 'type' => 'text', 'label' => 'Naam', 'verplicht' => true],
                            ['naam' => 'email', 'type' => 'email', 'label' => 'E-mailadres', 'verplicht' => true],
                            ['naam' => 'telefoon', 'type' => 'tel', 'label' => 'Telefoonnummer', 'verplicht' => false],
                            ['naam' => 'bericht', 'type' => 'textarea', 'label' => 'Bericht', 'verplicht' => true],
                        ],
                        'email_naar' => '',
                        'bevestiging' => 'Bedankt voor uw bericht. Wij nemen zo snel mogelijk contact met u op.',
                        'knop_tekst' => 'Versturen',
                    ]
                ]]);
            }

            // Mark setup as complete
            $siteData = load_json('site.json');
            $siteData['setup_complete'] = true;
            $siteData['show_welcome'] = true;
            $siteData['company']['copyright_year'] = date('Y');
            save_json('site.json', $siteData);

            require_once __DIR__ . '/includes/audit.php';
            audit_log('setup_voltooid', "Admin: {$naam} ({$email})", $naam);

            header('Location: /setup.php?step=5');
            exit;
        }
    }
}

$siteData = load_json('site.json');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASEO CMS — Installatie</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-2xl">
    <!-- Progress steps -->
    <div class="flex items-center justify-center mb-8">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                    <?= $i < $step ? 'bg-green-500 text-white' : ($i === $step ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') ?>">
                    <?= $i < $step ? '&#10003;' : $i ?>
                </div>
                <?php if ($i < 5): ?>
                <div class="w-12 h-0.5 <?= $i < $step ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-8">
        <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <?php foreach ($errors as $err): ?>
            <p class="text-sm"><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <!-- Step 1: Company info -->
        <h1 class="text-2xl font-bold mb-2">Welkom bij EASEO CMS</h1>
        <p class="text-gray-500 mb-6">Vul de gegevens van uw bedrijf in om te beginnen.</p>

        <form method="POST">
            <input type="hidden" name="action" value="step1">
            <input type="hidden" name="step" value="1">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bedrijfsnaam *</label>
                    <input type="text" name="bedrijfsnaam" value="<?= htmlspecialchars($siteData['company']['name'] ?? '') ?>" required class="w-full border border-gray-300 rounded-lg p-2.5" autofocus>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                    <input type="text" name="tagline" value="<?= htmlspecialchars($siteData['company']['tagline'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5" placeholder="Korte beschrijving van uw bedrijf">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($siteData['company']['email'] ?? '') ?>" required class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefoonnummer</label>
                    <input type="tel" name="telefoon" value="<?= htmlspecialchars($siteData['company']['phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                    <input type="text" name="adres" value="<?= htmlspecialchars($siteData['company']['address'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Postcode</label>
                    <input type="text" name="postcode" value="<?= htmlspecialchars($siteData['company']['postcode'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plaats</label>
                    <input type="text" name="plaats" value="<?= htmlspecialchars($siteData['company']['city'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">KVK-nummer</label>
                    <input type="text" name="kvk" value="<?= htmlspecialchars($siteData['company']['kvk'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">BTW-nummer</label>
                    <input type="text" name="btw" value="<?= htmlspecialchars($siteData['company']['btw'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700">Volgende &rarr;</button>
            </div>
        </form>

        <?php elseif ($step === 2): ?>
        <!-- Step 2: Branding -->
        <h1 class="text-2xl font-bold mb-2">Huisstijl</h1>
        <p class="text-gray-500 mb-6">Kies de kleuren en lettertypen voor uw website.</p>

        <form method="POST">
            <input type="hidden" name="action" value="step2">
            <input type="hidden" name="step" value="2">

            <div class="mb-6">
                <h3 class="font-medium mb-3">Kleuren</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php
                    $colorFields = [
                        'color_primary' => ['Primair', '#2563EB'],
                        'color_secondary' => ['Secundair', '#EA580C'],
                        'color_dark' => ['Donker', '#111827'],
                        'color_muted' => ['Gedempt', '#9CA3AF'],
                    ];
                    foreach ($colorFields as $key => [$label, $default]):
                        $val = $siteData['brand'][$key] ?? $default;
                    ?>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1"><?= $label ?></label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>" class="w-10 h-10 rounded cursor-pointer border-0">
                            <span class="text-xs text-gray-400"><?= $val ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="font-medium mb-3">Lettertypen</h3>
                <div class="grid grid-cols-2 gap-4">
                    <?php
                    $fonts = ['Outfit', 'Inter', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Raleway', 'Playfair Display', 'Merriweather', 'Nunito', 'DM Sans', 'Plus Jakarta Sans'];
                    ?>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Titels</label>
                        <select name="font_display" class="w-full border border-gray-300 rounded-lg p-2.5">
                            <?php foreach ($fonts as $f): ?>
                            <option value="<?= $f ?>" <?= ($siteData['brand']['font_display'] ?? 'Outfit') === $f ? 'selected' : '' ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Tekst</label>
                        <select name="font_body" class="w-full border border-gray-300 rounded-lg p-2.5">
                            <?php foreach ($fonts as $f): ?>
                            <option value="<?= $f ?>" <?= ($siteData['brand']['font_body'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= $f ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <a href="/setup.php?step=1" class="text-gray-500 hover:text-gray-700 py-2.5">&larr; Vorige</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700">Volgende &rarr;</button>
            </div>
        </form>

        <?php elseif ($step === 3): ?>
        <!-- Step 3: Pages -->
        <h1 class="text-2xl font-bold mb-2">Pagina's instellen</h1>
        <p class="text-gray-500 mb-6">Pas de standaardteksten aan voor uw website.</p>

        <?php $contentData = load_json('content.json'); ?>
        <form method="POST">
            <input type="hidden" name="action" value="step3">
            <input type="hidden" name="step" value="3">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hero titel (homepage)</label>
                    <input type="text" name="hero_titel" value="<?= htmlspecialchars($contentData['home']['hero_titel'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hero tekst (homepage)</label>
                    <textarea name="hero_tekst" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5"><?= htmlspecialchars($contentData['home']['hero_tekst'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Over ons tekst</label>
                    <textarea name="over_tekst" rows="4" class="w-full border border-gray-300 rounded-lg p-2.5"><?= htmlspecialchars($contentData['over']['inhoud_tekst'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <a href="/setup.php?step=2" class="text-gray-500 hover:text-gray-700 py-2.5">&larr; Vorige</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700">Volgende &rarr;</button>
            </div>
        </form>

        <?php elseif ($step === 4): ?>
        <!-- Step 4: Admin account -->
        <h1 class="text-2xl font-bold mb-2">Admin account</h1>
        <p class="text-gray-500 mb-6">Maak uw beheeraccount aan.</p>

        <form method="POST">
            <input type="hidden" name="action" value="step4">
            <input type="hidden" name="step" value="4">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Naam *</label>
                    <input type="text" name="naam" required class="w-full border border-gray-300 rounded-lg p-2.5" autofocus>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mailadres *</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord * (min. 8 tekens)</label>
                    <input type="password" name="wachtwoord" required minlength="8" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord bevestigen *</label>
                    <input type="password" name="wachtwoord2" required minlength="8" class="w-full border border-gray-300 rounded-lg p-2.5">
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <a href="/setup.php?step=3" class="text-gray-500 hover:text-gray-700 py-2.5">&larr; Vorige</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700">Voltooien &rarr;</button>
            </div>
        </form>

        <?php elseif ($step === 5): ?>
        <!-- Step 5: Confirmation -->
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-2xl font-bold mb-2">Installatie voltooid!</h1>
            <p class="text-gray-500 mb-8">Uw EASEO CMS is klaar voor gebruik. U kunt nu inloggen in het beheerpaneel.</p>

            <div class="flex justify-center gap-4">
                <a href="/" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Website bekijken</a>
                <a href="/beheer/?tab=login" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-medium hover:bg-blue-700">Naar beheer</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
