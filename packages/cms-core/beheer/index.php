<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Admin router
 *
 * Entry-point assumption: App::boot() has already been called by the parent
 * entry-file (e.g. apps/easeo-website/public/beheer-entry.php) so that
 * App::current() is non-null and modules are registered in the Registry.
 * If this file is included without a prior boot, module tabs are simply
 * unavailable (graceful degradation — the core tabs still work).
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/helpers.php';
$tab = $_GET['tab'] ?? 'dashboard';
// Retrieve module-registered beheer-pages (may be empty if App not booted yet)
$_easeoApp = \Easeo\Cms\App::current();
$_modulePages = $_easeoApp !== null ? $_easeoApp->registry()->beheerPages()->all() : [];
// 2FA verification page
if (!empty($_SESSION['2fa_pending']) && !is_logged_in()) {
    $maskedEmail = mask_email($_SESSION['2fa_pending']['email']);
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php 
    echo Translator::translate('admin_2fa_title');
    ?> — <?php 
    echo Translator::translate('admin_login_title');
    ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/beheer/assets/admin.css">
    </head>
    <body class="admin-body flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8">
            <div class="admin-card">
                <h1 class="text-2xl font-bold text-white mb-2 text-center"><?php 
    echo Translator::translate('admin_2fa_title');
    ?></h1>
                <p class="text-gray-400 text-sm text-center mb-6"><?php 
    echo Translator::translate('admin_2fa_sent_to');
    ?> <?php 
    echo ContentRepository::escape($maskedEmail);
    ?></p>

                <?php 
    $error = flash_error();
    if ($error) {
        ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm"><?php 
        echo ContentRepository::escape($error);
        ?></div>
                <?php 
    }
    ?>

                <?php 
    $success = flash_success();
    if ($success) {
        ?>
                <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg text-sm"><?php 
        echo ContentRepository::escape($success);
        ?></div>
                <?php 
    }
    ?>

                <form method="POST" action="/beheer/">
                    <?php 
    echo csrf_field();
    ?>
                    <div class="mb-6">
                        <label for="2fa_code" class="block text-sm font-medium text-gray-300 mb-1"><?php 
    echo Translator::translate('admin_2fa_code_label');
    ?></label>
                        <input type="text" id="2fa_code" name="2fa_code" required autofocus
                               class="admin-input w-full text-center text-2xl tracking-widest"
                               maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                               placeholder="000000" autocomplete="one-time-code">
                    </div>
                    <button type="submit" name="verify_2fa" class="btn-admin btn-admin-primary w-full py-2.5"><?php 
    echo Translator::translate('admin_2fa_verify_button');
    ?></button>
                </form>

                <form method="POST" action="/beheer/" class="mt-4 text-center">
                    <?php 
    echo csrf_field();
    ?>
                    <button type="submit" name="resend_2fa" class="text-sm text-blue-400 hover:text-blue-300"><?php 
    echo Translator::translate('admin_2fa_resend_button');
    ?></button>
                </form>

                <div class="mt-4 text-center">
                    <a href="/beheer/?tab=login" class="text-sm text-gray-500 hover:text-gray-400">&larr; <?php 
    echo Translator::translate('admin_2fa_back_to_login');
    ?></a>
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
        <title><?php 
    echo Translator::translate('admin_login_button');
    ?> — <?php 
    echo Translator::translate('admin_login_title');
    ?></title>
        <meta name="robots" content="noindex, nofollow">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="/beheer/assets/admin.css">
    </head>
    <body class="admin-body flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8">
            <div class="admin-card">
                <h1 class="text-2xl font-bold text-white mb-6 text-center"><?php 
    echo Translator::translate('admin_login_title');
    ?></h1>

                <?php 
    if (isset($_GET['timeout'])) {
        ?>
                <div class="mb-4 p-3 bg-yellow-900/50 border border-yellow-700 text-yellow-300 rounded-lg text-sm"><?php 
        echo Translator::translate('admin_session_timeout');
        ?></div>
                <?php 
    }
    ?>

                <?php 
    $error = flash_error();
    if ($error) {
        ?>
                <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm"><?php 
        echo ContentRepository::escape($error);
        ?></div>
                <?php 
    }
    ?>

                <form method="POST" action="/beheer/?tab=login">
                    <?php 
    echo csrf_field();
    ?>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1"><?php 
    echo Translator::translate('admin_login_email_label');
    ?></label>
                        <input type="email" id="email" name="email" required autofocus class="admin-input w-full" placeholder="admin@voorbeeld.nl">
                    </div>
                    <div class="mb-6">
                        <label for="wachtwoord" class="block text-sm font-medium text-gray-300 mb-1"><?php 
    echo Translator::translate('admin_login_password_label');
    ?></label>
                        <input type="password" id="wachtwoord" name="wachtwoord" required class="admin-input w-full">
                    </div>
                    <button type="submit" class="btn-admin btn-admin-primary w-full py-2.5"><?php 
    echo Translator::translate('admin_login_button');
    ?></button>
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
// Core tab whitelist
$allowedTabs = ['dashboard', 'content', 'paginas', 'blog', 'blog-edit', 'media', 'formulieren', 'formulier-edit', 'inbox', 'navigatie', 'huisstijl', 'redirects', 'juridisch', 'tracking', 'email', 'gebruikers', 'activiteit', 'backup', 'geheimen'];
// Admin-only core tabs
$adminTabs = ['gebruikers', 'activiteit', 'backup', 'geheimen'];

// Check if the requested tab is a module-registered beheer-page
$_isModuleTab = isset($_modulePages[$tab]);

if (!in_array($tab, $allowedTabs) && !$_isModuleTab) {
    // Unknown tab: redirect to dashboard (avoids a confusing blank page)
    $tab = 'dashboard';
    $_isModuleTab = false;
}

if (in_array($tab, $adminTabs)) {
    require_admin();
}

// Module tab: enforce admin-only flag, then invoke its renderer
if ($_isModuleTab) {
    $_page = $_modulePages[$tab];
    if (!empty($_page['admin_only']) && !is_admin()) {
        http_response_code(403);
        $adminPageTitle = 'Geen toegang';
        require_once __DIR__ . '/inc/layout-top.php';
        echo '<div class="admin-card"><p class="text-gray-400">Geen toegang — deze pagina is uitsluitend beschikbaar voor beheerders.</p></div>';
        require_once __DIR__ . '/inc/layout-bottom.php';
        exit;
    }
    $adminPageTitle = $_page['label'];
    require_once __DIR__ . '/inc/layout-top.php';
    ob_start();
    try {
        $result = ($_page['renderer'])();
        $output = ob_get_clean();
        echo $output;
        if ($result !== null) {
            echo is_string($result) ? $result : (string) $result;
        }
    } catch (\Throwable $e) {
        ob_end_clean();
        error_log('[easeo/cms-core beheer] module tab renderer for "' . $tab . '" threw: ' . $e->getMessage());
        echo '<div class="admin-card"><p class="text-red-400">Fout bij het laden van deze module-pagina.</p></div>';
    }
    require_once __DIR__ . '/inc/layout-bottom.php';
    exit;
}

// Core tab dispatch
$adminPageTitle = ucfirst($tab);
require_once __DIR__ . '/inc/layout-top.php';
$pageFile = __DIR__ . '/pages/' . $tab . '.php';
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    echo '<div class="admin-card"><p class="text-gray-400">' . Translator::translate('admin_page_not_found') . '</p></div>';
}
require_once __DIR__ . '/inc/layout-bottom.php';
