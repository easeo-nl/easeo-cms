<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Blog\BlogEngine;
use Easeo\Cms\Seo\StructuredData;
/**
 * EASEO CMS — Blog overview with pagination and category filter
 */
require_once __DIR__ . '/../vendor/autoload.php';
ContentRepository::checkSetup();
$posts = BlogEngine::getPublishedPosts();
$categories = BlogEngine::getCategories();
// Category filter
$filterCat = $_GET['categorie'] ?? '';
if ($filterCat) {
    $posts = array_filter($posts, fn($p) => strcasecmp($p['categorie'] ?? '', $filterCat) === 0);
}
$page = max(1, (int) ($_GET['pagina'] ?? 1));
$result = BlogEngine::paginatePosts(array_values($posts), $page);
$pageTitle = Translator::translate('blog_page_title') . ($filterCat ? ' — ' . $filterCat : '') . ' | ' . ContentRepository::siteValue('company.name', 'EASEO');
$metaDescription = Translator::translate('blog_meta_description');
$structuredSchemas = [StructuredData::schemaBreadcrumbs('Blog', 'blog')];
require_once EASEO_TEMPLATES . '/layout/header.php';
?>

<section class="py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-dark mb-2"><?php 
echo Translator::translate('blog_page_title');
?></h1>
            <p class="text-muted"><?php 
echo Translator::translate('blog_page_subtitle');
?></p>
        </div>

        <?php 
if (!empty($categories)) {
    ?>
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="/blog" class="px-3 py-1 rounded-full text-sm <?php 
    echo !$filterCat ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';
    ?> transition-colors"><?php 
    echo Translator::translate('blog_filter_all');
    ?></a>
            <?php 
    foreach ($categories as $cat) {
        ?>
            <a href="/blog/categorie/<?php 
        echo urlencode($cat);
        ?>"
               class="px-3 py-1 rounded-full text-sm <?php 
        echo $filterCat === $cat ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';
        ?> transition-colors">
                <?php 
        echo ContentRepository::escape($cat);
        ?>
            </a>
            <?php 
    }
    ?>
        </div>
        <?php 
}
?>

        <?php 
if (empty($result['posts'])) {
    ?>
        <div class="text-center py-12">
            <p class="text-muted"><?php 
    echo Translator::translate('blog_no_posts_found');
    ?></p>
        </div>
        <?php 
} else {
    ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
    foreach ($result['posts'] as $post) {
        ?>
                <?php 
        echo BlogEngine::renderPostCard($post);
        ?>
            <?php 
    }
    ?>
        </div>

        <?php 
    if ($result['total_pages'] > 1) {
        ?>
        <nav class="flex justify-center items-center gap-2 mt-10">
            <?php 
        if ($result['page'] > 1) {
            ?>
            <a href="/blog/pagina/<?php 
            echo $result['page'] - 1;
            echo $filterCat ? '?categorie=' . urlencode($filterCat) : '';
            ?>"
               class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">&laquo; <?php 
            echo Translator::translate('pagination_previous');
            ?></a>
            <?php 
        }
        ?>

            <?php 
        for ($i = 1; $i <= $result['total_pages']; $i++) {
            ?>
            <a href="/blog/pagina/<?php 
            echo $i;
            echo $filterCat ? '?categorie=' . urlencode($filterCat) : '';
            ?>"
               class="px-3 py-2 rounded text-sm <?php 
            echo $i === $result['page'] ? 'bg-primary text-white' : 'bg-gray-100 hover:bg-gray-200';
            ?>">
                <?php 
            echo $i;
            ?>
            </a>
            <?php 
        }
        ?>

            <?php 
        if ($result['page'] < $result['total_pages']) {
            ?>
            <a href="/blog/pagina/<?php 
            echo $result['page'] + 1;
            echo $filterCat ? '?categorie=' . urlencode($filterCat) : '';
            ?>"
               class="px-4 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm"><?php 
            echo Translator::translate('pagination_next');
            ?> &raquo;</a>
            <?php 
        }
        ?>
        </nav>
        <?php 
    }
    ?>
        <?php 
}
?>
    </div>
</section>

<?php 
require_once EASEO_TEMPLATES . '/layout/footer.php';
