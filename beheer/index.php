<?php
/**
 * EASEO CMS — Admin router
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';

$tab = $_GET['tab'] ?? 'dashboard';

// 2FA verification page
if (!empty($_SESSION['2fa_pending']) && !is_logged_in()) {
    $maskedEmail = mask_email($_SESSION['2fa_pending']['email']);
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= t('admin_2fa_title') ?> — <?= t('admin_login_title') ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/beheer/assets/admin.css">
    </head>
    <body class="admin-body flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8">
            <div class="admin-card">
                <h1 class="text-2xl font-bold text-white mb-2 text-center"><?= t('admin_2fa_title') ?></h1>
                <p class="text-gray-400 text-sm text-center mb-6"><?= t('admin_2fa_sent_to') ?> <?= e($maskedEmail) ?></p>

                <?php $error = flash_error(); if ($error): ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm"><?= e($error) ?></div>
                <?php endif; ?>

                <?php $success = flash_success(); if ($success): ?>
                <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg text-sm"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="/beheer/">
                    <?= csrf_field() ?>
                    <div class="mb-6">
                        <label for="2fa_code" class="block text-sm font-medium text-gray-300 mb-1"><?= t('admin_2fa_code_label') ?></label>
                        <input type="text" id="2fa_code" name="2fa_code" required autofocus
                               class="admin-input w-full text-center text-2xl tracking-widest"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               placeholder="000000" autocomplete="one-time-code">
                    </div>
                    <button type="submit" name="verify_2fa" class="btn-admin btn-admin-primary w-full py-2.5"><?= t('admin_2fa_verify_button') ?></button>
                </form>

                <form method="POST" action="/beheer/" class="mt-4 text-center">
                    <?= csrf_field() ?>
                    <button type="submit" name="resend_2fa" class="text-sm text-blue-400 hover:text-blue-300"><?= t('admin_2fa_resend_button') ?></button>
                </form>

                <div class="mt-4 text-center">
                    <a href="/beheer/?tab=login" class="text-sm text-gray-500 hover:text-gray-400">&larr; <?= t('admin_2fa_back_to_login') ?></a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Login page (no auth required)
if ($tab === 'login') {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= t('admin_login_button') ?> — <?= t('admin_login_title') ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/beheer/assets/admin.css">
    </head>
    <body class="admin-body flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8">
            <div class="admin-card">
                <h1 class="text-2xl font-bold text-white mb-6 text-center"><?= t('admin_login_title') ?></h1>

                <?php if (isset($_GET['timeout'])): ?>
                <div class="mb-4 p-3 bg-yellow-900/50 border border-yellow-700 text-yellow-300 rounded-lg text-sm"><?= t('admin_session_timeout') ?></div>
                <?php endif; ?>

                <?php $error = flash_error(); if ($error): ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="/beheer/?tab=login">
                    <?= csrf_field() ?>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1"><?= t('admin_login_email_label') ?></label>
                        <input type="email" id="email" name="email" required autofocus class="admin-input w-full" placeholder="admin@voorbeeld.nl">
                    </div>
                    <div class="mb-6">
                        <label for="wachtwoord" class="block text-sm font-medium text-gray-300 mb-1"><?= t('admin_login_password_label') ?></label>
                        <input type="password" id="wachtwoord" name="wachtwoord" required class="admin-input w-full">
                    </div>
                    <button type="submit" class="btn-admin btn-admin-primary w-full py-2.5"><?= t('admin_login_button') ?></button>
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
    echo '<div class="admin-card"><p class="text-gray-400">' . t('admin_page_not_found') . '</p></div>';
}

require_once __DIR__ . '/inc/layout-bottom.php';
