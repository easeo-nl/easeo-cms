<?php
/**
 * EASEO CMS — Cookie policy page
 */
require_once __DIR__ . '/../vendor/autoload.php';
check_setup();

$pageTitle = 'Cookiebeleid | ' . site('company.name', 'EASEO');
$metaDescription = 'Cookiebeleid van ' . site('company.name', 'ons bedrijf');

require_once EASEO_CORE . '/src/legacy/header.php';
?>

<section class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <div class="content-area">
            <?= nl2br(e(get_legal_text('cookies'))) ?>
        </div>
    </div>
</section>

<?php require_once EASEO_CORE . '/src/legacy/footer.php'; ?>
