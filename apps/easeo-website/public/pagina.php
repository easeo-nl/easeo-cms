<?php
/**
 * EASEO CMS — Dynamic page renderer
 * Renders content pages from content.json based on slug
 */
require_once __DIR__ . '/includes/content.php';
check_setup();

$slug = $_GET['slug'] ?? '';

// Check if page exists in content
$pageData = page_content($slug);

if (empty($pageData) || !is_array($pageData)) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$pageTitle = ($pageData['meta_title'] ?? ucfirst($slug)) . ' | ' . site('company.name', 'EASEO');
$metaDescription = $pageData['meta_description'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <?php if (!empty($pageData['titel'])): ?>
        <h1 class="text-3xl md:text-4xl font-display font-bold text-dark mb-6"><?= e($pageData['titel']) ?></h1>
        <?php endif; ?>

        <?php if (!empty($pageData['intro_tekst'])): ?>
        <p class="text-lg text-muted mb-8"><?= e($pageData['intro_tekst']) ?></p>
        <?php endif; ?>

        <?php if (!empty($pageData['afbeelding'])): ?>
        <img src="<?= e($pageData['afbeelding']) ?>" alt="<?= e($pageData['titel'] ?? '') ?>" class="w-full rounded-lg shadow mb-8">
        <?php endif; ?>

        <?php if (!empty($pageData['inhoud_tekst'])): ?>
        <div class="content-area">
            <?= nl2br(e($pageData['inhoud_tekst'])) ?>
        </div>
        <?php endif; ?>

        <?php
        // Render any additional text/image fields dynamically
        foreach ($pageData as $key => $value):
            if (in_array($key, ['meta_title', 'meta_description', 'titel', 'intro_tekst', 'inhoud_tekst', 'afbeelding', 'formulier_id', 'kaart_embed'])) continue;
            if (empty($value)) continue;

            // Section fields (sectieN_titel / sectieN_tekst)
            if (preg_match('/^sectie\d+_titel$/', $key)):
                $num = preg_replace('/\D/', '', $key);
                $sectionText = $pageData["sectie{$num}_tekst"] ?? '';
        ?>
        <section class="py-8">
            <h2 class="text-2xl font-display font-bold text-dark mb-3"><?= e($value) ?></h2>
            <?php if ($sectionText): ?>
            <p class="text-muted"><?= nl2br(e($sectionText)) ?></p>
            <?php endif; ?>
        </section>
        <?php
            endif;
        endforeach;
        ?>

        <?php
        // Render form if formulier_id is set
        if (!empty($pageData['formulier_id'])):
            require_once __DIR__ . '/includes/form-engine.php';
        ?>
        <div class="mt-8">
            <?= render_form($pageData['formulier_id']) ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
