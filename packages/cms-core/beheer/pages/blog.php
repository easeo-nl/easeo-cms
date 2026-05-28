<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Blog post list in admin
 */
require_once EASEO_ROOT . '/includes/blog-engine.php';
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $postId = $_POST['post_id'] ?? '';
        $post = get_post_by_id($postId);
        if ($post && delete_post($postId)) {
            audit_log('blog_verwijderd', "Post: {$post['titel']}");
            $_SESSION['flash_success'] = Translator::translate('success_post_deleted');
        }
    }
    header('Location: /beheer/?tab=blog');
    exit;
}
$posts = get_posts();
usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo Translator::translate('blog_list_title');
?></h1>
    <a href="/beheer/?tab=blog-edit" class="btn-admin btn-admin-primary"><?php 
echo Translator::translate('blog_new_post_button');
?></a>
</div>

<div class="admin-card">
    <?php 
if (empty($posts)) {
    ?>
        <p class="text-gray-500"><?php 
    echo Translator::translate('blog_no_posts');
    ?></p>
    <?php 
} else {
    ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php 
    echo Translator::translate('table_header_title');
    ?></th>
                <th><?php 
    echo Translator::translate('table_header_category');
    ?></th>
                <th><?php 
    echo Translator::translate('table_header_status');
    ?></th>
                <th><?php 
    echo Translator::translate('table_header_date');
    ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php 
    foreach ($posts as $post) {
        ?>
            <tr>
                <td class="text-white">
                    <a href="/beheer/?tab=blog-edit&id=<?php 
        echo ContentRepository::escape($post['id']);
        ?>" class="hover:text-blue-400">
                        <?php 
        echo ContentRepository::escape($post['titel'] ?? Translator::translate('post_untitled'));
        ?>
                    </a>
                </td>
                <td class="text-gray-400"><?php 
        echo ContentRepository::escape($post['categorie'] ?? '');
        ?></td>
                <td>
                    <?php 
        if (($post['status'] ?? '') === 'gepubliceerd') {
            ?>
                        <span class="badge badge-success"><?php 
            echo Translator::translate('status_published');
            ?></span>
                    <?php 
        } else {
            ?>
                        <span class="badge badge-warning"><?php 
            echo Translator::translate('status_draft');
            ?></span>
                    <?php 
        }
        ?>
                </td>
                <td class="text-gray-500"><?php 
        echo ContentRepository::escape($post['datum'] ?? '');
        ?></td>
                <td class="text-right">
                    <a href="/beheer/?tab=blog-edit&id=<?php 
        echo ContentRepository::escape($post['id']);
        ?>" class="text-blue-400 hover:text-blue-300 text-sm mr-2"><?php 
        echo Translator::translate('action_edit');
        ?></a>
                    <form method="POST" class="inline" onsubmit="return confirm('<?php 
        echo Translator::translate('confirm_delete');
        ?>')">
                        <?php 
        echo csrf_field();
        ?>
                        <input type="hidden" name="post_id" value="<?php 
        echo ContentRepository::escape($post['id']);
        ?>">
                        <button type="submit" name="delete_post" class="text-red-400 hover:text-red-300 text-sm"><?php 
        echo Translator::translate('action_delete');
        ?></button>
                    </form>
                </td>
            </tr>
            <?php 
    }
    ?>
        </tbody>
    </table>
    <?php 
}
?>
</div>
<?php 
