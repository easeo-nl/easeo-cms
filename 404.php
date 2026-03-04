<?php
/**
 * EASEO CMS — 404 Not Found page
 */
require_once __DIR__ . '/includes/content.php';
http_response_code(404);

$pageTitle = 'Pagina niet gevonden | ' . site('company.name', 'EASEO');
$metaDescription = '';

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-20">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="text-8xl font-display font-bold text-primary mb-4">404</h1>
        <h2 class="text-2xl font-display font-bold text-dark mb-4">Pagina niet gevonden</h2>
        <p class="text-muted mb-8">De pagina die u zoekt bestaat niet of is verplaatst.</p>
        <a href="/" class="btn btn-primary">Terug naar home</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
