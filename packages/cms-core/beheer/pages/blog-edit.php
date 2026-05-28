<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Blog\BlogEngine;
/**
 * EASEO CMS — Blog post editor with media picker
 */
require_once EASEO_ROOT . '/includes/blog-engine.php';
$postId = $_GET['id'] ?? '';
$post = $postId ? BlogEngine::getPostById($postId) : null;
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $data = ['titel' => sanitize_input($_POST['titel'] ?? ''), 'samenvatting' => sanitize_input($_POST['samenvatting'] ?? ''), 'inhoud' => $_POST['inhoud'] ?? '', 'afbeelding' => sanitize_input($_POST['afbeelding'] ?? ''), 'categorie' => sanitize_input($_POST['categorie'] ?? ''), 'tags' => sanitize_input($_POST['tags'] ?? ''), 'auteur' => sanitize_input($_POST['auteur'] ?? ''), 'status' => sanitize_input($_POST['status'] ?? 'concept'), 'datum' => sanitize_input($_POST['datum'] ?? date('Y-m-d H:i:s')), 'meta_title' => strip_tags(sanitize_input($_POST['meta_title'] ?? '')), 'meta_description' => strip_tags(sanitize_input($_POST['meta_description'] ?? ''))];
        if (empty($data['titel'])) {
            $_SESSION['flash_error'] = Translator::translate('error_title_required');
        } else {
            if ($post) {
                BlogEngine::updatePost($post['id'], $data);
                audit_log('blog_bewerkt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = Translator::translate('success_post_updated');
                header('Location: /beheer/?tab=blog-edit&id=' . $post['id']);
            } else {
                $newPost = BlogEngine::createPost($data);
                audit_log('blog_aangemaakt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = Translator::translate('success_post_created');
                header('Location: /beheer/?tab=blog-edit&id=' . $newPost['id']);
            }
            exit;
        }
    }
}
// Reload post after save
if ($postId && !$post) {
    $post = BlogEngine::getPostById($postId);
}
$categories = BlogEngine::getCategories();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo $post ? Translator::translate('blog_edit_title') : Translator::translate('blog_new_title');
?></h1>
    <a href="/beheer/?tab=blog" class="btn-admin btn-admin-outline text-sm">&larr; <?php 
echo Translator::translate('button_back');
?></a>
</div>

<form method="POST" class="space-y-6">
    <?php 
echo csrf_field();
?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_title');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_title');
?>">?</span></label>
                    <input type="text" name="titel" value="<?php 
echo ContentRepository::escape($post['titel'] ?? '');
?>" required class="admin-input w-full text-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_summary');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_summary');
?>">?</span></label>
                    <textarea name="samenvatting" rows="2" class="admin-input w-full"><?php 
echo ContentRepository::escape($post['samenvatting'] ?? '');
?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_content');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_content');
?>">?</span></label>
                    <textarea name="inhoud" rows="15" class="admin-input w-full"><?php 
echo ContentRepository::escape($post['inhoud'] ?? '');
?></textarea>
                </div>
            </div>

            <!-- SEO -->
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3"><?php 
echo Translator::translate('section_seo');
?></h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_meta_title');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_meta_title');
?>">?</span></label>
                    <input type="text" name="meta_title" id="blog-meta-title" value="<?php 
echo ContentRepository::escape($post['meta_title'] ?? '');
?>" class="admin-input w-full" maxlength="60">
                    <p class="text-xs text-gray-500 mt-1"><span id="blog-meta-title-count"><?php 
echo strlen($post['meta_title'] ?? '');
?></span><?php 
echo Translator::translate('char_count_of_60');
?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_meta_description');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_meta_description');
?>">?</span></label>
                    <textarea name="meta_description" id="blog-meta-desc" rows="2" class="admin-input w-full" maxlength="155"><?php 
echo ContentRepository::escape($post['meta_description'] ?? '');
?></textarea>
                    <p class="text-xs text-gray-500 mt-1"><span id="blog-meta-desc-count"><?php 
echo strlen($post['meta_description'] ?? '');
?></span><?php 
echo Translator::translate('char_count_of_155');
?></p>
                </div>

                <!-- SEO Preview -->
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <p class="text-xs text-gray-500 mb-2"><?php 
echo Translator::translate('google_preview_label');
?></p>
                    <div style="background:#0f172a;border:1px solid #334155;border-radius:8px;padding:16px;">
                        <p style="color:#8ab4f8;font-size:13px;margin:0 0 4px;" id="blog-seo-url"><?php 
echo ContentRepository::escape($_SERVER['HTTP_HOST'] ?? 'domein.nl');
?>/blog/<?php 
echo ContentRepository::escape($post['slug'] ?? '');
?></p>
                        <p style="color:#e8eaed;font-size:16px;margin:0 0 4px;" id="blog-seo-title"><?php 
echo ContentRepository::escape($post['meta_title'] ?? $post['titel'] ?? '');
?></p>
                        <p style="color:#bdc1c6;font-size:13px;margin:0;line-height:1.4;" id="blog-seo-desc"><?php 
echo ContentRepository::escape($post['meta_description'] ?? $post['samenvatting'] ?? '');
?></p>
                    </div>
                </div>
            </div>

            <script>
            (function() {
                var mt = document.getElementById('blog-meta-title');
                var md = document.getElementById('blog-meta-desc');
                var titel = document.querySelector('input[name="titel"]');
                function updateBlogPreview() {
                    document.getElementById('blog-seo-title').textContent = mt.value || (titel ? titel.value : '');
                    document.getElementById('blog-seo-desc').textContent = md.value || '';
                }
                mt.addEventListener('input', function() {
                    var c = document.getElementById('blog-meta-title-count');
                    c.textContent = this.value.length;
                    c.style.color = this.value.length > 60 ? '#ef4444' : '';
                    updateBlogPreview();
                });
                md.addEventListener('input', function() {
                    var c = document.getElementById('blog-meta-desc-count');
                    c.textContent = this.value.length;
                    c.style.color = this.value.length > 155 ? '#ef4444' : '';
                    updateBlogPreview();
                });
                if (titel) titel.addEventListener('input', updateBlogPreview);
            })();
            </script>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3"><?php 
echo Translator::translate('section_publish');
?></h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_status');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_status');
?>">?</span></label>
                    <select name="status" class="admin-input w-full">
                        <option value="concept" <?php 
echo ($post['status'] ?? '') === 'concept' ? 'selected' : '';
?>><?php 
echo Translator::translate('status_draft');
?></option>
                        <option value="gepubliceerd" <?php 
echo ($post['status'] ?? '') === 'gepubliceerd' ? 'selected' : '';
?>><?php 
echo Translator::translate('status_published');
?></option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_date');
?></label>
                    <input type="datetime-local" name="datum"
                           value="<?php 
echo ContentRepository::escape(date('Y-m-d\\TH:i', strtotime($post['datum'] ?? 'now')));
?>"
                           class="admin-input w-full">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_author');
?></label>
                    <input type="text" name="auteur" value="<?php 
echo ContentRepository::escape($post['auteur'] ?? current_user()['naam'] ?? '');
?>" class="admin-input w-full">
                </div>

                <button type="submit" name="save_post" class="btn-admin btn-admin-primary w-full">
                    <?php 
echo $post ? Translator::translate('button_update') : Translator::translate('button_publish');
?>
                </button>
            </div>

            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3"><?php 
echo Translator::translate('section_details');
?></h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_category');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_blog_category');
?>">?</span></label>
                    <input type="text" name="categorie" value="<?php 
echo ContentRepository::escape($post['categorie'] ?? '');
?>" class="admin-input w-full"
                           list="categories" placeholder="<?php 
echo Translator::translate('placeholder_category_example');
?>">
                    <datalist id="categories">
                        <?php 
foreach ($categories as $cat) {
    ?>
                        <option value="<?php 
    echo ContentRepository::escape($cat);
    ?>">
                        <?php 
}
?>
                    </datalist>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_tags');
?></label>
                    <input type="text" name="tags" value="<?php 
echo ContentRepository::escape($post['tags'] ?? '');
?>" class="admin-input w-full" placeholder="tag1, tag2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_featured_image');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_featured_image');
?>">?</span></label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="afbeelding" id="post-afbeelding" value="<?php 
echo ContentRepository::escape($post['afbeelding'] ?? '');
?>" class="admin-input flex-1" placeholder="/images/uploads/...">
                        <button type="button" onclick="openMediaPicker('post-afbeelding')" class="btn-admin-sm"><?php 
echo Translator::translate('button_choose_media');
?></button>
                    </div>
                    <?php 
if (!empty($post['afbeelding'])) {
    ?>
                    <img src="<?php 
    echo ContentRepository::escape($post['afbeelding']);
    ?>" class="mt-2 w-full h-32 object-cover rounded" alt="">
                    <?php 
}
?>
                </div>
            </div>

            <?php 
if ($post) {
    ?>
            <div class="admin-card">
                <p class="text-xs text-gray-500"><?php 
    echo Translator::translate('label_slug');
    ?> <?php 
    echo ContentRepository::escape($post['slug'] ?? '');
    ?> <span class="help-tooltip" data-help="<?php 
    echo Translator::translate('tooltip_post_slug');
    ?>">?</span></p>
                <p class="text-xs text-gray-500 mt-1"><?php 
    echo Translator::translate('label_updated');
    ?> <?php 
    echo ContentRepository::escape($post['bijgewerkt'] ?? '');
    ?></p>
                <a href="/blog/<?php 
    echo ContentRepository::escape($post['slug']);
    ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-300 mt-2 inline-block"><?php 
    echo Translator::translate('link_view_post');
    ?></a>
            </div>
            <?php 
}
?>
        </div>
    </div>
</form>
<?php 
