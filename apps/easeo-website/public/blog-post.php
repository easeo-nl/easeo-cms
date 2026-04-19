<?php
/**
 * EASEO CMS — Individual blog post with Schema.org
 */
require_once __DIR__ . '/../vendor/autoload.php';
check_setup();

$slug = $_GET['slug'] ?? '';
$post = get_post_by_slug($slug);

if (!$post || ($post['status'] ?? 'concept') !== 'gepubliceerd') {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$pageTitle = ($post['meta_title'] ?: $post['titel']) . ' | ' . site('company.name', 'EASEO');
$metaDescription = $post['meta_description'] ?: $post['samenvatting'] ?: '';

$structuredSchemas = [
    schema_article($post),
    schema_breadcrumbs($post['titel'], 'blog/' . $post['slug'], [
        ['name' => 'Blog', 'slug' => 'blog'],
    ]),
];

require_once EASEO_CORE . '/src/legacy/header.php';

$dateISO = date('c', strtotime($post['datum']));
?>

<article class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <!-- Breadcrumb -->
        <nav class="text-sm text-muted mb-6">
            <a href="/" class="hover:text-primary"><?= t('breadcrumb_home') ?></a>
            <span class="mx-2">/</span>
            <a href="/blog" class="hover:text-primary"><?= t('breadcrumb_blog') ?></a>
            <span class="mx-2">/</span>
            <span><?= e($post['titel']) ?></span>
        </nav>

        <?php if ($post['categorie']): ?>
        <span class="text-sm font-medium text-primary uppercase tracking-wider"><?= e($post['categorie']) ?></span>
        <?php endif; ?>

        <h1 class="text-3xl md:text-4xl font-display font-bold text-dark mt-2 mb-4"><?= e($post['titel']) ?></h1>

        <div class="flex items-center gap-4 text-sm text-muted mb-8">
            <?php if ($post['auteur']): ?>
            <span><?= t('blog_post_author_prefix', ['author' => e($post['auteur'])]) ?></span>
            <span class="text-gray-300">|</span>
            <?php endif; ?>
            <time datetime="<?= $dateISO ?>"><?= date('d F Y', strtotime($post['datum'])) ?></time>
        </div>

        <?php if ($post['afbeelding']): ?>
        <img src="<?= e($post['afbeelding']) ?>" alt="<?= e($post['titel']) ?>" class="w-full rounded-lg shadow mb-8">
        <?php endif; ?>

        <div class="content-area text-dark leading-relaxed">
            <?= nl2br(e($post['inhoud'] ?? '')) ?>
        </div>

        <?php if ($post['tags']): ?>
        <div class="mt-8 pt-6 border-t">
            <div class="flex flex-wrap gap-2">
                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm"><?= e(trim($tag)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back link -->
        <div class="mt-10 pt-6 border-t">
            <a href="/blog" class="text-primary hover:underline">&larr; <?= t('blog_post_back_link') ?></a>
        </div>
    </div>
</article>

<?php require_once EASEO_CORE . '/src/legacy/footer.php'; ?>
