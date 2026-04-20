<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * Template: Latest blog posts section
 * Expects: $data array with titel, tekst, aantal (number of posts)
 */
require_once EASEO_ROOT . '/includes/blog-engine.php';
$titel = ContentRepository::escape($data['titel'] ?? t('blog_latest_default_title'));
$tekst = ContentRepository::escape($data['tekst'] ?? '');
$aantal = (int) ($data['aantal'] ?? 3);
$posts = get_published_posts();
usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
$posts = array_slice($posts, 0, $aantal);
if (!empty($posts)) {
    ?>
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-display font-bold text-dark mb-4"><?php 
    echo $titel;
    ?></h2>
            <?php 
    if ($tekst) {
        ?>
            <p class="text-lg text-muted max-w-2xl mx-auto"><?php 
        echo $tekst;
        ?></p>
            <?php 
    }
    ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-<?php 
    echo min(3, count($posts));
    ?> gap-6">
            <?php 
    foreach ($posts as $post) {
        ?>
                <?php 
        echo render_post_card($post);
        ?>
            <?php 
    }
    ?>
        </div>

        <div class="text-center mt-8">
            <a href="/blog" class="text-primary font-medium hover:underline"><?php 
    echo t('blog_latest_view_all_link');
    ?></a>
        </div>
    </div>
</section>
<?php 
}
