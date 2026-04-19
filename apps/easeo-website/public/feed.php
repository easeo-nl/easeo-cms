<?php
/**
 * EASEO CMS — RSS feed
 */
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/blog-engine.php';

header('Content-Type: application/rss+xml; charset=UTF-8');

$posts = get_published_posts();
usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
$posts = array_slice($posts, 0, 20);

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?= e(site('company.name', 'EASEO')) ?> — Blog</title>
    <link><?= e($baseUrl) ?>/blog</link>
    <description><?= e(site('company.tagline', '')) ?></description>
    <language>nl</language>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <atom:link href="<?= e($baseUrl) ?>/feed" rel="self" type="application/rss+xml" />

    <?php foreach ($posts as $post): ?>
    <item>
        <title><?= e($post['titel'] ?? '') ?></title>
        <link><?= e($baseUrl) ?>/blog/<?= e($post['slug'] ?? '') ?></link>
        <guid isPermaLink="true"><?= e($baseUrl) ?>/blog/<?= e($post['slug'] ?? '') ?></guid>
        <pubDate><?= date('r', strtotime($post['datum'] ?? 'now')) ?></pubDate>
        <description><![CDATA[<?= $post['samenvatting'] ?? '' ?>]]></description>
        <?php if ($post['categorie'] ?? ''): ?>
        <category><?= e($post['categorie']) ?></category>
        <?php endif; ?>
        <?php if ($post['auteur'] ?? ''): ?>
        <author><?= e($post['auteur']) ?></author>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
