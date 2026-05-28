<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Blog\BlogEngine;
/**
 * EASEO CMS — Auto-generated XML sitemap
 */
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/xml; charset=UTF-8');
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$pages = ContentRepository::loadJson('content.json');
$posts = BlogEngine::getPublishedPosts();
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?php 
echo ContentRepository::escape($baseUrl);
?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Content pages -->
    <?php 
foreach (array_keys($pages) as $slug) {
    if ($slug === 'home') {
        continue;
    }
    $loc = $baseUrl . '/' . $slug;
    ?>
    <url>
        <loc><?php 
    echo ContentRepository::escape($loc);
    ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php 
}
?>

    <!-- Blog overview -->
    <url>
        <loc><?php 
echo ContentRepository::escape($baseUrl);
?>/blog</loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Blog posts -->
    <?php 
foreach ($posts as $post) {
    $loc = $baseUrl . '/blog/' . ($post['slug'] ?? '');
    $lastmod = date('Y-m-d', strtotime($post['bijgewerkt'] ?? $post['datum'] ?? 'now'));
    ?>
    <url>
        <loc><?php 
    echo ContentRepository::escape($loc);
    ?></loc>
        <lastmod><?php 
    echo $lastmod;
    ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php 
}
?>

    <!-- Dynamic pages (pages.json) -->
    <?php 
$dynamicPages = ContentRepository::loadJson('pages.json');
foreach ($dynamicPages['pages'] ?? [] as $dp) {
    if ($dp['status'] !== 'published') {
        continue;
    }
    $loc = $baseUrl . '/' . $dp['slug'];
    $lastmod = $dp['updated_at'] ?? $dp['created_at'] ?? date('Y-m-d');
    ?>
    <url>
        <loc><?php 
    echo ContentRepository::escape($loc);
    ?></loc>
        <lastmod><?php 
    echo ContentRepository::escape($lastmod);
    ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php 
}
?>

    <!-- Legal pages -->
    <url>
        <loc><?php 
echo ContentRepository::escape($baseUrl);
?>/privacyverklaring</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?php 
echo ContentRepository::escape($baseUrl);
?>/voorwaarden</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?php 
echo ContentRepository::escape($baseUrl);
?>/cookiebeleid</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
<?php 
