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
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
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
            'meta_title' => sanitize_input($_POST['meta_title'] ?? ''),
            'meta_description' => sanitize_input($_POST['meta_description'] ?? ''),
        ];

        if (empty($data['titel'])) {
            $_SESSION['flash_error'] = 'Titel is verplicht.';
        } else {
            if ($post) {
                update_post($post['id'], $data);
                audit_log('blog_bewerkt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = 'Blogpost bijgewerkt.';
                header('Location: /beheer/?tab=blog-edit&id=' . $post['id']);
            } else {
                $newPost = create_post($data);
                audit_log('blog_aangemaakt', "Post: {$data['titel']}");
                $_SESSION['flash_success'] = 'Blogpost aangemaakt.';
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
    <h1 class="text-2xl font-bold text-white"><?= $post ? 'Blogpost bewerken' : 'Nieuw blogpost' ?></h1>
    <a href="/beheer/?tab=blog" class="btn-admin btn-admin-outline text-sm">&larr; Terug</a>
</div>

<form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Titel</label>
                    <input type="text" name="titel" value="<?= e($post['titel'] ?? '') ?>" required class="admin-input w-full text-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Samenvatting</label>
                    <textarea name="samenvatting" rows="2" class="admin-input w-full"><?= e($post['samenvatting'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Inhoud</label>
                    <textarea name="inhoud" rows="15" class="admin-input w-full"><?= e($post['inhoud'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SEO -->
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">SEO</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Meta titel</label>
                    <input type="text" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>" class="admin-input w-full">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Meta beschrijving</label>
                    <textarea name="meta_description" rows="2" class="admin-input w-full"><?= e($post['meta_description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">Publiceren</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                    <select name="status" class="admin-input w-full">
                        <option value="concept" <?= ($post['status'] ?? '') === 'concept' ? 'selected' : '' ?>>Concept</option>
                        <option value="gepubliceerd" <?= ($post['status'] ?? '') === 'gepubliceerd' ? 'selected' : '' ?>>Gepubliceerd</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Datum</label>
                    <input type="datetime-local" name="datum"
                           value="<?= e(date('Y-m-d\TH:i', strtotime($post['datum'] ?? 'now'))) ?>"
                           class="admin-input w-full">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Auteur</label>
                    <input type="text" name="auteur" value="<?= e($post['auteur'] ?? (current_user()['naam'] ?? '')) ?>" class="admin-input w-full">
                </div>

                <button type="submit" name="save_post" class="btn-admin btn-admin-primary w-full">
                    <?= $post ? 'Bijwerken' : 'Publiceren' ?>
                </button>
            </div>

            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">Details</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Categorie</label>
                    <input type="text" name="categorie" value="<?= e($post['categorie'] ?? '') ?>" class="admin-input w-full"
                           list="categories" placeholder="bijv. Nieuws">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Tags (komma gescheiden)</label>
                    <input type="text" name="tags" value="<?= e($post['tags'] ?? '') ?>" class="admin-input w-full" placeholder="tag1, tag2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Uitgelichte afbeelding</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="afbeelding" id="post-afbeelding" value="<?= e($post['afbeelding'] ?? '') ?>" class="admin-input flex-1" placeholder="/images/uploads/...">
                        <button type="button" onclick="openMediaPicker('post-afbeelding')" class="btn-admin-sm">Kies</button>
                    </div>
                    <?php if (!empty($post['afbeelding'])): ?>
                    <img src="<?= e($post['afbeelding']) ?>" class="mt-2 w-full h-32 object-cover rounded" alt="">
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($post): ?>
            <div class="admin-card">
                <p class="text-xs text-gray-500">Slug: <?= e($post['slug'] ?? '') ?></p>
                <p class="text-xs text-gray-500 mt-1">Bijgewerkt: <?= e($post['bijgewerkt'] ?? '') ?></p>
                <a href="/blog/<?= e($post['slug']) ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-300 mt-2 inline-block">Bekijk post &rarr;</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>
