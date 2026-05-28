<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Blog\BlogEngine;
/**
 * EASEO CMS — RSS feed
 */
require_once __DIR__ . '/../vendor/autoload.php';
header('Content-Type: application/rss+xml; charset=UTF-8');
$posts = BlogEngine::getPublishedPosts();
usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
$posts = array_slice($posts, 0, 20);
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'EASEO'));
?> — Blog</title>
    <link><?php 
echo ContentRepository::escape($baseUrl);
?>/blog</link>
    <description><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.tagline', ''));
?></description>
    <language>nl</language>
    <lastBuildDate><?php 
echo date('r');
?></lastBuildDate>
    <atom:link href="<?php 
echo ContentRepository::escape($baseUrl);
?>/feed" rel="self" type="application/rss+xml" />

    <?php 
foreach ($posts as $post) {
    ?>
    <item>
        <title><?php 
    echo ContentRepository::escape($post['titel'] ?? '');
    ?></title>
        <link><?php 
    echo ContentRepository::escape($baseUrl);
    ?>/blog/<?php 
    echo ContentRepository::escape($post['slug'] ?? '');
    ?></link>
        <guid isPermaLink="true"><?php 
    echo ContentRepository::escape($baseUrl);
    ?>/blog/<?php 
    echo ContentRepository::escape($post['slug'] ?? '');
    ?></guid>
        <pubDate><?php 
    echo date('r', strtotime($post['datum'] ?? 'now'));
    ?></pubDate>
        <description><![CDATA[<?php 
    echo $post['samenvatting'] ?? '';
    ?>]]></description>
        <?php 
    if ($post['categorie'] ?? '') {
        ?>
        <category><?php 
        echo ContentRepository::escape($post['categorie']);
        ?></category>
        <?php 
    }
    ?>
        <?php 
    if ($post['auteur'] ?? '') {
        ?>
        <author><?php 
        echo ContentRepository::escape($post['auteur']);
        ?></author>
        <?php 
    }
    ?>
    </item>
    <?php 
}
?>
</channel>
</rss>
<?php 
