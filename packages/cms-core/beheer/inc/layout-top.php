<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Admin layout top (HTML head + sidebar)
 */
$adminPageTitle = $adminPageTitle ?? 'Beheer';
$activeTab = $_GET['tab'] ?? 'dashboard';
// Count unread submissions for badge
$submissions = [];
foreach (glob(EASEO_DATA . '/submissions/*.json') as $file) {
    $sub = json_decode(file_get_contents($file), true);
    if ($sub && empty($sub['gelezen'])) {
        $submissions[] = $sub;
    }
}
$unreadCount = count($submissions);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
echo ContentRepository::escape($adminPageTitle);
?> — EASEO Beheer</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/beheer/assets/admin.css">
</head>
<body class="admin-body">

<!-- Mobile menu button -->
<button id="admin-mobile-toggle" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-gray-800 rounded-lg text-white">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="/beheer/" class="text-white no-underline"><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'CMS'));
?></a>
    </div>

    <nav class="py-4">
        <?php 
$navItems = [['tab' => 'dashboard', 'label' => t('nav_dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1'], ['tab' => 'content', 'label' => t('nav_content'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'], ['tab' => 'paginas', 'label' => t('nav_pages'), 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'], ['tab' => 'blog', 'label' => t('nav_blog'), 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'], ['tab' => 'media', 'label' => t('nav_media'), 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'], ['tab' => 'formulieren', 'label' => t('nav_forms'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'], ['tab' => 'inbox', 'label' => t('nav_inbox') . ($unreadCount > 0 ? " ({$unreadCount})" : ''), 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'], 'divider', ['tab' => 'navigatie', 'label' => t('nav_navigation'), 'icon' => 'M4 6h16M4 12h16M4 18h7'], ['tab' => 'huisstijl', 'label' => t('nav_branding'), 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'], ['tab' => 'redirects', 'label' => t('nav_redirects'), 'icon' => 'M13 7l5 5m0 0l-5 5m5-5H6'], ['tab' => 'juridisch', 'label' => t('nav_legal'), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'], ['tab' => 'tracking', 'label' => t('nav_tracking'), 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'], ['tab' => 'email', 'label' => t('nav_email'), 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'], 'divider', ['tab' => 'gebruikers', 'label' => t('nav_users'), 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'admin' => true], ['tab' => 'activiteit', 'label' => t('nav_activity'), 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'admin' => true], ['tab' => 'backup', 'label' => t('nav_backup'), 'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4', 'admin' => true]];
foreach ($navItems as $item) {
    if ($item === 'divider') {
        ?>
            <div class="admin-nav-divider"></div>
        <?php 
    } else {
        if (!empty($item['admin']) && !is_admin()) {
            continue;
        }
        $isActive = $activeTab === $item['tab'] ? ' active' : '';
        ?>
            <a href="/beheer/?tab=<?php 
        echo $item['tab'];
        ?>" class="admin-nav-item<?php 
        echo $isActive;
        ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="<?php 
        echo $item['icon'];
        ?>"/>
                </svg>
                <?php 
        echo $item['label'];
        ?>
            </a>
        <?php 
    }
}
?>

        <div class="admin-nav-divider"></div>
        <a href="/beheer/?tab=logout" class="admin-nav-item text-red-400">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <?php 
echo t('nav_logout');
?>
        </a>

        <div class="px-6 py-4 text-xs text-gray-600">
            <?php 
echo t('nav_logged_in_as');
?> <?php 
echo ContentRepository::escape(current_user()['naam'] ?? '');
?>
        </div>
    </nav>
</aside>

<!-- Main content -->
<div class="admin-content">
    <?php 
echo render_flash();
