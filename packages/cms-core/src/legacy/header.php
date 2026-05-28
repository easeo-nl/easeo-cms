<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Branding\BrandConfig;
/**
 * EASEO CMS — Site header
 */
require_once __DIR__ . '/brand.php';
require_once __DIR__ . '/navigation.php';
require_once __DIR__ . '/structured-data.php';
$page_title = $pageTitle ?? ContentRepository::siteValue('company.name', 'EASEO CMS');
$meta_desc = $metaDescription ?? '';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
echo ContentRepository::escape($page_title);
?></title>
    <?php 
if ($meta_desc) {
    ?>
    <meta name="description" content="<?php 
    echo ContentRepository::escape($meta_desc);
    ?>">
    <?php 
}
?>

    <?php 
if (ContentRepository::siteValue('brand.favicon')) {
    ?>
    <link rel="icon" href="<?php 
    echo ContentRepository::escape(ContentRepository::siteValue('brand.favicon'));
    ?>">
    <?php 
}
?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php 
echo BrandConfig::googleFontsUrl();
?>" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script><?php 
echo BrandConfig::tailwindConfig();
?></script>

    <style><?php 
echo BrandConfig::cssProperties();
?></style>
    <link rel="stylesheet" href="/css/custom.css">

    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">

    <?php 
include __DIR__ . '/tracking-head.php';
?>

    <?php 
// Structured data (JSON-LD)
$structuredSchemas = $structuredSchemas ?? [];
$structuredSchemas[] = schema_organization();
$currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($currentUri === '/' || $currentUri === '/index.php') {
    $structuredSchemas[] = schema_website();
}
render_structured_data($structuredSchemas);
?>
</head>
<body class="font-body text-dark bg-white min-h-screen flex flex-col">
    <?php 
include __DIR__ . '/tracking-body.php';
?>

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">
                <a href="/" class="flex items-center space-x-3 shrink-0">
                    <?php 
if (ContentRepository::siteValue('brand.logo')) {
    ?>
                        <img src="<?php 
    echo ContentRepository::escape(ContentRepository::siteValue('brand.logo'));
    ?>" alt="<?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.name'));
    ?>" class="h-10 w-auto">
                    <?php 
} else {
    ?>
                        <span class="text-xl font-display font-bold text-primary"><?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'EASEO'));
    ?></span>
                    <?php 
}
?>
                </a>

                <?php
echo \Easeo\Cms\Navigation\Menu::renderMainNav();
?>

                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-md text-gray-700 hover:bg-gray-100" aria-label="<?php 
echo Translator::translate('aria_label_menu');
?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php
echo \Easeo\Cms\Navigation\Menu::renderMobileNav();
?>
    </header>

    <main class="flex-1">
<?php 
