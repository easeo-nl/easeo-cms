<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Blog\BlogEngine;
/**
 * EASEO CMS — Individual blog post with Schema.org
 */
require_once __DIR__ . '/../vendor/autoload.php';
ContentRepository::checkSetup();
$slug = $_GET['slug'] ?? '';
$post = BlogEngine::getPostBySlug($slug);
if (!$post || ($post['status'] ?? 'concept') !== 'gepubliceerd') {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}
$pageTitle = ($post['meta_title'] ?: $post['titel']) . ' | ' . ContentRepository::siteValue('company.name', 'EASEO');
$metaDescription = ($post['meta_description'] ?: $post['samenvatting']) ?: '';
$structuredSchemas = [schema_article($post), schema_breadcrumbs($post['titel'], 'blog/' . $post['slug'], [['name' => 'Blog', 'slug' => 'blog']])];
require_once EASEO_CORE . '/src/legacy/header.php';
$dateISO = date('c', strtotime($post['datum']));
?>

<article class="py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">
        <!-- Breadcrumb -->
        <nav class="text-sm text-muted mb-6">
            <a href="/" class="hover:text-primary"><?php 
echo Translator::translate('breadcrumb_home');
?></a>
            <span class="mx-2">/</span>
            <a href="/blog" class="hover:text-primary"><?php 
echo Translator::translate('breadcrumb_blog');
?></a>
            <span class="mx-2">/</span>
            <span><?php 
echo ContentRepository::escape($post['titel']);
?></span>
        </nav>

        <?php 
if ($post['categorie']) {
    ?>
        <span class="text-sm font-medium text-primary uppercase tracking-wider"><?php 
    echo ContentRepository::escape($post['categorie']);
    ?></span>
        <?php 
}
?>

        <h1 class="text-3xl md:text-4xl font-display font-bold text-dark mt-2 mb-4"><?php 
echo ContentRepository::escape($post['titel']);
?></h1>

        <div class="flex items-center gap-4 text-sm text-muted mb-8">
            <?php 
if ($post['auteur']) {
    ?>
            <span><?php 
    echo Translator::translate('blog_post_author_prefix', ['author' => ContentRepository::escape($post['auteur'])]);
    ?></span>
            <span class="text-gray-300">|</span>
            <?php 
}
?>
            <time datetime="<?php 
echo $dateISO;
?>"><?php 
echo date('d F Y', strtotime($post['datum']));
?></time>
        </div>

        <?php 
if ($post['afbeelding']) {
    ?>
        <img src="<?php 
    echo ContentRepository::escape($post['afbeelding']);
    ?>" alt="<?php 
    echo ContentRepository::escape($post['titel']);
    ?>" class="w-full rounded-lg shadow mb-8">
        <?php 
}
?>

        <div class="content-area text-dark leading-relaxed">
            <?php 
echo nl2br(ContentRepository::escape($post['inhoud'] ?? ''));
?>
        </div>

        <?php 
if ($post['tags']) {
    ?>
        <div class="mt-8 pt-6 border-t">
            <div class="flex flex-wrap gap-2">
                <?php 
    foreach (explode(',', $post['tags']) as $tag) {
        ?>
                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm"><?php 
        echo ContentRepository::escape(trim($tag));
        ?></span>
                <?php 
    }
    ?>
            </div>
        </div>
        <?php 
}
?>

        <!-- Back link -->
        <div class="mt-10 pt-6 border-t">
            <a href="/blog" class="text-primary hover:underline">&larr; <?php 
echo Translator::translate('blog_post_back_link');
?></a>
        </div>
    </div>
</article>

<?php 
require_once EASEO_CORE . '/src/legacy/footer.php';
