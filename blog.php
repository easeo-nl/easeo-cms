<?php
/**
 * EASEO CMS — Blog overview with pagination and category filter
 */
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/blog-engine.php';
check_setup();

$posts = get_published_posts();
$categories = get_categories();

// Category filter
$filterCat = $_GET['categorie'] ?? '';
if ($filterCat) {
    $posts = array_filter($posts, fn($p) => strcasecmp($p['categorie'] ?? '', $filterCat) === 0);
}

$page = max(1, (int)($_GET['pagina'] ?? 1));
$result = paginate_posts(array_values($posts), $page);

$pageTitle = 'Blog' . ($filterCat ? ' — ' . $filterCat : '') . ' | ' . site('company.name', 'EASEO');
$metaDescription = 'Bekijk onze laatste blogposts en artikelen.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-dark mb-2">Blog</h1>
            <p class="text-muted">Laatste nieuws en artikelen</p>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="/blog" class="px-3 py-1 rounded-full text-sm <?= !$filterCat ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition-colors">Alles</a>
            <?php foreach ($categories as $cat): ?>
            <a href="/blog/categorie/<?= urlencode($cat) ?>"
               class="px-3 py-1 rounded-full text-sm <?= $filterCat === $cat ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> transition-colors">
                <?= e($cat) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($result['posts'])): ?>
        <div class="text-center py-12">
            <p class="text-muted">Geen blogposts gevonden.</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($result['posts'] as $post): ?>
                <?= render_post_card($post) ?>
            <?php endforeach; ?>
        </div>

        <?php if ($result['total_pages'] > 1): ?>
        <nav class="flex justify-center items-center gap-2 mt-10">
            <?php if ($result['page'] > 1): ?>
            <a href="/blog/pagina/<?= $result['page'] - 1 ?><?= $filterCat ? '?categorie=' . urlencode($filterCat) : '' ?>"
               class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">&laquo; Vorige</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
            <a href="/blog/pagina/<?= $i ?><?= $filterCat ? '?categorie=' . urlencode($filterCat) : '' ?>"
               class="px-3 py-2 rounded text-sm <?= $i === $result['page'] ? 'bg-primary text-white' : 'bg-gray-100 hover:bg-gray-200' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($result['page'] < $result['total_pages']): ?>
            <a href="/blog/pagina/<?= $result['page'] + 1 ?><?= $filterCat ? '?categorie=' . urlencode($filterCat) : '' ?>"
               class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">Volgende &raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
