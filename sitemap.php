<?php
/**
 * EASEO CMS — Auto-generated XML sitemap
 */
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/blog-engine.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$pages = load_json('content.json');
$posts = get_published_posts();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= e($baseUrl) ?>/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Content pages -->
    <?php foreach (array_keys($pages) as $slug):
        if ($slug === 'home') continue;
        $loc = $baseUrl . '/' . $slug;
    ?>
    <url>
        <loc><?= e($loc) ?></loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Blog overview -->
    <url>
        <loc><?= e($baseUrl) ?>/blog</loc>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Blog posts -->
    <?php foreach ($posts as $post):
        $loc = $baseUrl . '/blog/' . ($post['slug'] ?? '');
        $lastmod = date('Y-m-d', strtotime($post['bijgewerkt'] ?? $post['datum'] ?? 'now'));
    ?>
    <url>
        <loc><?= e($loc) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

    <!-- Dynamic pages (pages.json) -->
    <?php
    $dynamicPages = load_json('pages.json');
    foreach (($dynamicPages['pages'] ?? []) as $dp):
        if ($dp['status'] !== 'published') continue;
        $loc = $baseUrl . '/' . $dp['slug'];
        $lastmod = $dp['updated_at'] ?? $dp['created_at'] ?? date('Y-m-d');
    ?>
    <url>
        <loc><?= e($loc) ?></loc>
        <lastmod><?= e($lastmod) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

    <!-- Legal pages -->
    <url>
        <loc><?= e($baseUrl) ?>/privacyverklaring</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/voorwaarden</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= e($baseUrl) ?>/cookiebeleid</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
