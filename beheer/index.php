<?php
/**
 * EASEO CMS — Admin router
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

$tab = $_GET['tab'] ?? 'dashboard';

// Login page (no auth required)
if ($tab === 'login') {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inloggen — EASEO Beheer</title>
        <meta name="robots" content="noindex, nofollow">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/beheer/assets/admin.css">
    </head>
    <body class="admin-body flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8">
            <div class="admin-card">
                <h1 class="text-2xl font-bold text-white mb-6 text-center">EASEO Beheer</h1>

                <?php $error = flash_error(); if ($error): ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="/beheer/?tab=login">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">E-mailadres</label>
                        <input type="email" id="email" name="email" required autofocus class="admin-input w-full" placeholder="admin@voorbeeld.nl">
                    </div>
                    <div class="mb-6">
                        <label for="wachtwoord" class="block text-sm font-medium text-gray-300 mb-1">Wachtwoord</label>
                        <input type="password" id="wachtwoord" name="wachtwoord" required class="admin-input w-full">
                    </div>
                    <button type="submit" class="btn-admin btn-admin-primary w-full py-2.5">Inloggen</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// All other tabs require login
require_login();

// Route to pages
$allowedTabs = [
    'dashboard', 'content', 'paginas', 'blog', 'blog-edit', 'media',
    'formulieren', 'formulier-edit', 'inbox',
    'navigatie', 'huisstijl', 'redirects', 'juridisch', 'tracking', 'email',
    'gebruikers', 'activiteit', 'backup',
];

if (!in_array($tab, $allowedTabs)) {
    $tab = 'dashboard';
}

// Admin-only tabs
$adminTabs = ['gebruikers', 'activiteit', 'backup'];
if (in_array($tab, $adminTabs)) {
    require_admin();
}

$adminPageTitle = ucfirst($tab);
require_once __DIR__ . '/inc/layout-top.php';

$pageFile = __DIR__ . '/pages/' . $tab . '.php';
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    echo '<div class="admin-card"><p class="text-gray-400">Pagina niet gevonden.</p></div>';
}

require_once __DIR__ . '/inc/layout-bottom.php';
