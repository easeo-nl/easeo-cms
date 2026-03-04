<?php
/**
 * EASEO CMS — Blog post list in admin
 */
require_once EASEO_ROOT . '/includes/blog-engine.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $postId = $_POST['post_id'] ?? '';
        $post = get_post_by_id($postId);
        if ($post && delete_post($postId)) {
            audit_log('blog_verwijderd', "Post: {$post['titel']}");
            $_SESSION['flash_success'] = 'Blogpost verwijderd.';
        }
    }
    header('Location: /beheer/?tab=blog');
    exit;
}

$posts = get_posts();
usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Blog</h1>
    <a href="/beheer/?tab=blog-edit" class="btn-admin btn-admin-primary">+ Nieuw bericht</a>
</div>

<div class="admin-card">
    <?php if (empty($posts)): ?>
        <p class="text-gray-500">Nog geen blogposts.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Titel</th>
                <th>Categorie</th>
                <th>Status</th>
                <th>Datum</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td class="text-white">
                    <a href="/beheer/?tab=blog-edit&id=<?= e($post['id']) ?>" class="hover:text-blue-400">
                        <?= e($post['titel'] ?? 'Zonder titel') ?>
                    </a>
                </td>
                <td class="text-gray-400"><?= e($post['categorie'] ?? '') ?></td>
                <td>
                    <?php if (($post['status'] ?? '') === 'gepubliceerd'): ?>
                        <span class="badge badge-success">Gepubliceerd</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Concept</span>
                    <?php endif; ?>
                </td>
                <td class="text-gray-500"><?= e($post['datum'] ?? '') ?></td>
                <td class="text-right">
                    <a href="/beheer/?tab=blog-edit&id=<?= e($post['id']) ?>" class="text-blue-400 hover:text-blue-300 text-sm mr-2">Bewerken</a>
                    <form method="POST" class="inline" onsubmit="return confirm('Verwijderen?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="post_id" value="<?= e($post['id']) ?>">
                        <button type="submit" name="delete_post" class="text-red-400 hover:text-red-300 text-sm">Verwijderen</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
