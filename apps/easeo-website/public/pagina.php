<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Form\FormEngine;
/**
 * EASEO CMS — Dynamic page renderer
 * Renders content pages from content.json based on slug
 */
require_once __DIR__ . '/../vendor/autoload.php';
ContentRepository::checkSetup();
$slug = $_GET['slug'] ?? '';
// Check if page exists in content
$pageData = ContentRepository::pageContent($slug);
if (empty($pageData) || !is_array($pageData)) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}
$pageTitle = ($pageData['meta_title'] ?? ucfirst($slug)) . ' | ' . ContentRepository::siteValue('company.name', 'EASEO');
$metaDescription = $pageData['meta_description'] ?? '';
require_once EASEO_CORE . '/src/legacy/header.php';
?>

<section class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <?php 
if (!empty($pageData['titel'])) {
    ?>
        <h1 class="text-3xl md:text-4xl font-display font-bold text-dark mb-6"><?php 
    echo ContentRepository::escape($pageData['titel']);
    ?></h1>
        <?php 
}
?>

        <?php 
if (!empty($pageData['intro_tekst'])) {
    ?>
        <p class="text-lg text-muted mb-8"><?php 
    echo ContentRepository::escape($pageData['intro_tekst']);
    ?></p>
        <?php 
}
?>

        <?php 
if (!empty($pageData['afbeelding'])) {
    ?>
        <img src="<?php 
    echo ContentRepository::escape($pageData['afbeelding']);
    ?>" alt="<?php 
    echo ContentRepository::escape($pageData['titel'] ?? '');
    ?>" class="w-full rounded-lg shadow mb-8">
        <?php 
}
?>

        <?php 
if (!empty($pageData['inhoud_tekst'])) {
    ?>
        <div class="content-area">
            <?php 
    echo nl2br(ContentRepository::escape($pageData['inhoud_tekst']));
    ?>
        </div>
        <?php 
}
?>

        <?php 
// Render any additional text/image fields dynamically
foreach ($pageData as $key => $value) {
    if (in_array($key, ['meta_title', 'meta_description', 'titel', 'intro_tekst', 'inhoud_tekst', 'afbeelding', 'formulier_id', 'kaart_embed'])) {
        continue;
    }
    if (empty($value)) {
        continue;
    }
    // Section fields (sectieN_titel / sectieN_tekst)
    if (preg_match('/^sectie\\d+_titel$/', $key)) {
        $num = preg_replace('/\\D/', '', $key);
        $sectionText = $pageData["sectie{$num}_tekst"] ?? '';
        ?>
        <section class="py-8">
            <h2 class="text-2xl font-display font-bold text-dark mb-3"><?php 
        echo ContentRepository::escape($value);
        ?></h2>
            <?php 
        if ($sectionText) {
            ?>
            <p class="text-muted"><?php 
            echo nl2br(ContentRepository::escape($sectionText));
            ?></p>
            <?php 
        }
        ?>
        </section>
        <?php 
    }
}
?>

        <?php 
// Render form if formulier_id is set
if (!empty($pageData['formulier_id'])) {
    // form-engine loaded via autoload/bootstrap
    ?>
        <div class="mt-8">
            <?php 
    echo FormEngine::render($pageData['formulier_id']);
    ?>
        </div>
        <?php 
}
?>
    </div>
</section>

<?php 
require_once EASEO_CORE . '/src/legacy/footer.php';
