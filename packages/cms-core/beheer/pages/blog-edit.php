<?php
/**
 * EASEO CMS — Blog post editor with media picker
 */
require_once EASEO_ROOT . '/includes/blog-engine.php';

$postId = $_GET['id'] ?? '';
$post = $postId ? get_post_by_id($postId) : null;

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $data = [
            'titel' => sanitize_input($_POST['titel'] ?? ''),
            'samenvatting' => sanitize_input($_POST['samenvatting'] ?? ''),
            'inhoud' => $_POST['inhoud'] ?? '',
            'afbeelding' => sanitize_input($_POST['afbeelding'] ?? ''),
            'categorie' => sanitize_input($_POST['categorie'] ?? ''),
            'tags' => sanitize_input($_POST['tags'] ?? ''),
            'auteur' => sanitize_input($_POST['auteur'] ?? ''),
            'status' => sanitize_input($_POST['status'] ?? 'concept'),
            'datum' => sanitize_input($_POST['datum'] ?? date('Y-m-d H:i:s')),
            'meta_title' => strip_tags(sanitize_input($_POST['meta_title'] ?? '')),
            'meta_description' => strip_tags(sanitize_input($_POST['meta_description'] ?? '')),
        ];

        if (empty($data['titel'])) {
            $_SESSION['flash_error'] = t('error_title_required');
        } else {
            if ($post) {
                update_post($post['id'], $data);
                audit_log('blog_bewerkt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = t('success_post_updated');
                header('Location: /beheer/?tab=blog-edit&id=' . $post['id']);
            } else {
                $newPost = create_post($data);
                audit_log('blog_aangemaakt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = t('success_post_created');
                header('Location: /beheer/?tab=blog-edit&id=' . $newPost['id']);
            }
            exit;
        }
    }
}

// Reload post after save
if ($postId && !$post) $post = get_post_by_id($postId);

$categories = get_categories();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?= $post ? t('blog_edit_title') : t('blog_new_title') ?></h1>
    <a href="/beheer/?tab=blog" class="btn-admin btn-admin-outline text-sm">&larr; <?= t('button_back') ?></a>
</div>

<form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_title') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_title') ?>">?</span></label>
                    <input type="text" name="titel" value="<?= e($post['titel'] ?? '') ?>" required class="admin-input w-full text-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_summary') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_summary') ?>">?</span></label>
                    <textarea name="samenvatting" rows="2" class="admin-input w-full"><?= e($post['samenvatting'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_content') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_content') ?>">?</span></label>
                    <textarea name="inhoud" rows="15" class="admin-input w-full"><?= e($post['inhoud'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SEO -->
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3"><?= t('section_seo') ?></h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_meta_title') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_meta_title') ?>">?</span></label>
                    <input type="text" name="meta_title" id="blog-meta-title" value="<?= e($post['meta_title'] ?? '') ?>" class="admin-input w-full" maxlength="60">
                    <p class="text-xs text-gray-500 mt-1"><span id="blog-meta-title-count"><?= strlen($post['meta_title'] ?? '') ?></span><?= t('char_count_of_60') ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_meta_description') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_meta_description') ?>">?</span></label>
                    <textarea name="meta_description" id="blog-meta-desc" rows="2" class="admin-input w-full" maxlength="155"><?= e($post['meta_description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1"><span id="blog-meta-desc-count"><?= strlen($post['meta_description'] ?? '') ?></span><?= t('char_count_of_155') ?></p>
                </div>

                <!-- SEO Preview -->
                <div class="mt-4 pt-4 border-t border-gray-700">
                    <p class="text-xs text-gray-500 mb-2"><?= t('google_preview_label') ?></p>
                    <div style="background:#0f172a;border:1px solid #334155;border-radius:8px;padding:16px;">
                        <p style="color:#8ab4f8;font-size:13px;margin:0 0 4px;" id="blog-seo-url"><?= e(($_SERVER['HTTP_HOST'] ?? 'domein.nl')) ?>/blog/<?= e($post['slug'] ?? '') ?></p>
                        <p style="color:#e8eaed;font-size:16px;margin:0 0 4px;" id="blog-seo-title"><?= e($post['meta_title'] ?? $post['titel'] ?? '') ?></p>
                        <p style="color:#bdc1c6;font-size:13px;margin:0;line-height:1.4;" id="blog-seo-desc"><?= e($post['meta_description'] ?? $post['samenvatting'] ?? '') ?></p>
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
                <h3 class="text-md font-semibold text-white mb-3"><?= t('section_publish') ?></h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_status') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_status') ?>">?</span></label>
                    <select name="status" class="admin-input w-full">
                        <option value="concept" <?= ($post['status'] ?? '') === 'concept' ? 'selected' : '' ?>><?= t('status_draft') ?></option>
                        <option value="gepubliceerd" <?= ($post['status'] ?? '') === 'gepubliceerd' ? 'selected' : '' ?>><?= t('status_published') ?></option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_date') ?></label>
                    <input type="datetime-local" name="datum"
                           value="<?= e(date('Y-m-d\TH:i', strtotime($post['datum'] ?? 'now'))) ?>"
                           class="admin-input w-full">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_author') ?></label>
                    <input type="text" name="auteur" value="<?= e($post['auteur'] ?? (current_user()['naam'] ?? '')) ?>" class="admin-input w-full">
                </div>

                <button type="submit" name="save_post" class="btn-admin btn-admin-primary w-full">
                    <?= $post ? t('button_update') : t('button_publish') ?>
                </button>
            </div>

            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3"><?= t('section_details') ?></h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_category') ?> <span class="help-tooltip" data-help="<?= t('tooltip_blog_category') ?>">?</span></label>
                    <input type="text" name="categorie" value="<?= e($post['categorie'] ?? '') ?>" class="admin-input w-full"
                           list="categories" placeholder="<?= t('placeholder_category_example') ?>">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_tags') ?></label>
                    <input type="text" name="tags" value="<?= e($post['tags'] ?? '') ?>" class="admin-input w-full" placeholder="tag1, tag2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_featured_image') ?> <span class="help-tooltip" data-help="<?= t('tooltip_featured_image') ?>">?</span></label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="afbeelding" id="post-afbeelding" value="<?= e($post['afbeelding'] ?? '') ?>" class="admin-input flex-1" placeholder="/images/uploads/...">
                        <button type="button" onclick="openMediaPicker('post-afbeelding')" class="btn-admin-sm"><?= t('button_choose_media') ?></button>
                    </div>
                    <?php if (!empty($post['afbeelding'])): ?>
                    <img src="<?= e($post['afbeelding']) ?>" class="mt-2 w-full h-32 object-cover rounded" alt="">
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($post): ?>
            <div class="admin-card">
                <p class="text-xs text-gray-500"><?= t('label_slug') ?> <?= e($post['slug'] ?? '') ?> <span class="help-tooltip" data-help="<?= t('tooltip_post_slug') ?>">?</span></p>
                <p class="text-xs text-gray-500 mt-1"><?= t('label_updated') ?> <?= e($post['bijgewerkt'] ?? '') ?></p>
                <a href="/blog/<?= e($post['slug']) ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-300 mt-2 inline-block"><?= t('link_view_post') ?></a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>
