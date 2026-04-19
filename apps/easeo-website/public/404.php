<?php
/**
 * EASEO CMS — 404 Not Found page
 */
require_once __DIR__ . '/../vendor/autoload.php';
http_response_code(404);

$pageTitle = t('error_404_title') . ' | ' . site('company.name', 'EASEO');
$metaDescription = '';

require_once EASEO_CORE . '/src/legacy/header.php';
?>

<section class="py-20">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="text-8xl font-display font-bold text-primary mb-4">404</h1>
        <h2 class="text-2xl font-display font-bold text-dark mb-4"><?= t('error_404_title') ?></h2>
        <p class="text-muted mb-8"><?= t('error_404_message') ?></p>
        <a href="/" class="btn btn-primary"><?= t('error_404_back_button') ?></a>
    </div>
</section>

<?php require_once EASEO_CORE . '/src/legacy/footer.php'; ?>
