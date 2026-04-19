<?php
/**
 * EASEO CMS — Dynamic page router
 * Renders pages from data/pages.json based on slug
 */
require_once __DIR__ . '/../vendor/autoload.php';
check_setup();

$slug = $_GET['slug'] ?? '';
$slug = rtrim($slug, '/');

// Load pages.json
$pages_file = __DIR__ . '/data/pages.json';
if (!file_exists($pages_file)) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pages_data = json_decode(file_get_contents($pages_file), true);
$page = null;

foreach (($pages_data['pages'] ?? []) as $p) {
    if ($p['slug'] === $slug && $p['status'] === 'published') {
        $page = $p;
        break;
    }
}

if (!$page) {
    // Fallback: check content.json (legacy content pages) — only for single-segment slugs
    if (strpos($slug, '/') === false) {
        $pageData = page_content($slug);
        if (!empty($pageData) && is_array($pageData)) {
            include __DIR__ . '/pagina.php';
            exit;
        }
    }

    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// SEO
$pageTitle = ($page['seo_title'] ?: $page['title']) . ' | ' . site('company.name', 'EASEO');
$metaDescription = $page['seo_description'] ?: '';

$breadcrumbParents = [];
if (!empty($page['parent'])) {
    foreach (($pages_data['pages'] ?? []) as $pp) {
        if ($pp['id'] === $page['parent']) {
            $breadcrumbParents[] = ['name' => $pp['title'], 'slug' => $pp['slug']];
            break;
        }
    }
}
$structuredSchemas = [schema_breadcrumbs($page['title'], $page['slug'], $breadcrumbParents)];

require_once EASEO_CORE . '/src/legacy/header.php';
?>

<section class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <?php if (!empty($page['image'])): ?>
        <img src="<?= e($page['image']) ?>" alt="<?= e($page['title']) ?>" class="w-full rounded-lg shadow mb-8">
        <?php endif; ?>

        <h1 class="text-3xl md:text-4xl font-display font-bold text-dark mb-6"><?= e($page['title']) ?></h1>

        <div class="content-area prose max-w-none">
            <?= $page['content'] ?>
        </div>

        <?php if ($page['template'] === 'contact'): ?>
            <?php
            // form-engine loaded via autoload/bootstrap
            $contactData = page_content('contact');
            $formId = $contactData['formulier_id'] ?? 'contact';
            ?>
            <div class="mt-12">
                <?= render_form($formId) ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once EASEO_CORE . '/src/legacy/footer.php'; ?>
