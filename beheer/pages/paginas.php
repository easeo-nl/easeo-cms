<?php
/**
 * EASEO CMS — Pagina's beheren (overzicht + editor)
 */
$pagesData = load_json('pages.json');
$pages = $pagesData['pages'] ?? [];
$action = $_GET['action'] ?? 'list';
$editId = $_GET['id'] ?? '';

// Generate unique ID
function generate_page_id(): string {
    return 'p_' . bin2hex(random_bytes(6));
}

// Generate slug from title
function slugify(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(
        ['á','à','ä','â','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','ú','ù','ü','û','ñ','ç'],
        ['a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','u','u','u','u','n','c'],
        $text
    );
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s]+/', '-', trim($text));
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Get parent pages (no sub-sub pages allowed)
function get_parent_pages(array $pages, string $excludeId = ''): array {
    $parents = [];
    foreach ($pages as $p) {
        if (empty($p['parent']) && $p['id'] !== $excludeId) {
            $parents[] = $p;
        }
    }
    return $parents;
}

// Find page by ID
function find_page(array $pages, string $id): ?array {
    foreach ($pages as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

// Check if page has children
function has_children(array $pages, string $id): bool {
    foreach ($pages as $p) {
        if ($p['parent'] === $id) return true;
    }
    return false;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $deleteId = $_POST['delete_id'] ?? '';
        if (has_children($pages, $deleteId)) {
            $_SESSION['flash_error'] = 'Deze pagina heeft subpagina\'s. Verwijder eerst de subpagina\'s.';
        } else {
            $deletedPage = find_page($pages, $deleteId);
            $pages = array_values(array_filter($pages, fn($p) => $p['id'] !== $deleteId));
            $pagesData['pages'] = $pages;
            save_json('pages.json', $pagesData);

            if ($deletedPage) {
                audit_log('pagina_verwijderd', "Pagina: {$deletedPage['title']}");

                // Create redirect if requested
                if (!empty($_POST['create_redirect'])) {
                    $redirects = load_json('redirects.json');
                    $redirects['redirects'][] = [
                        'van' => '/' . $deletedPage['slug'],
                        'naar' => '/',
                        'type' => '301',
                    ];
                    save_json('redirects.json', $redirects);
                }
            }

            $_SESSION['flash_success'] = 'Pagina verwijderd.';
        }
    }
    header('Location: /beheer/?tab=paginas');
    exit;
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $slug = sanitize_input($_POST['slug'] ?? '');
        $parent = sanitize_input($_POST['parent'] ?? '');
        $content = $_POST['content'] ?? '';
        $image = sanitize_input($_POST['image'] ?? '');
        $template = sanitize_input($_POST['template'] ?? 'default');
        $show_in_menu = !empty($_POST['show_in_menu']);
        $menu_label = sanitize_input($_POST['menu_label'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'draft');
        $seo_title = sanitize_input($_POST['seo_title'] ?? '');
        $seo_description = sanitize_input($_POST['seo_description'] ?? '');

        if (empty($title)) {
            $_SESSION['flash_error'] = 'Titel is verplicht.';
        } else {
            if (empty($slug)) {
                $slug = slugify($title);
            }

            // Add parent slug prefix if parent is set
            if ($parent) {
                $parentPage = find_page($pages, $parent);
                if ($parentPage) {
                    $ownSlug = basename($slug); // Get only the last segment
                    if (empty($ownSlug)) $ownSlug = slugify($title);
                    $slug = $parentPage['slug'] . '/' . $ownSlug;
                }
            } else {
                // Strip any parent prefix from slug if parent was removed
                if (strpos($slug, '/') !== false) {
                    $slug = basename($slug);
                }
            }

            $pageData = [
                'id' => $editId ?: generate_page_id(),
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'parent' => $parent ?: null,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
                'status' => $status,
                'show_in_menu' => $show_in_menu,
                'menu_label' => $menu_label,
                'seo_title' => $seo_title,
                'seo_description' => $seo_description,
                'image' => $image,
                'template' => $template,
                'created_at' => '',
                'updated_at' => date('Y-m-d'),
            ];

            if ($editId) {
                // Update existing
                foreach ($pages as $idx => $p) {
                    if ($p['id'] === $editId) {
                        $pageData['created_at'] = $p['created_at'] ?? date('Y-m-d');
                        // Update child slugs if parent slug changed
                        $oldSlug = $p['slug'];
                        if ($oldSlug !== $slug) {
                            foreach ($pages as $ci => $cp) {
                                if ($cp['parent'] === $editId) {
                                    $childOwnSlug = basename($cp['slug']);
                                    $pages[$ci]['slug'] = $slug . '/' . $childOwnSlug;
                                }
                            }
                        }
                        $pages[$idx] = $pageData;
                        break;
                    }
                }
                audit_log('pagina_bewerkt', "Pagina: {$title}");
                $_SESSION['flash_success'] = 'Pagina bijgewerkt.';
            } else {
                // Create new
                $pageData['created_at'] = date('Y-m-d');
                $pages[] = $pageData;
                audit_log('pagina_aangemaakt', "Pagina: {$title}");
                $_SESSION['flash_success'] = 'Pagina aangemaakt.';
            }

            $pagesData['pages'] = $pages;
            save_json('pages.json', $pagesData);
            header('Location: /beheer/?tab=paginas&action=edit&id=' . $pageData['id']);
            exit;
        }
    }
}

// Reload data after save
$pagesData = load_json('pages.json');
$pages = $pagesData['pages'] ?? [];

// Sort pages: parents first by sort_order, children after their parent
function sort_pages_hierarchically(array $pages): array {
    $parents = array_filter($pages, fn($p) => empty($p['parent']));
    usort($parents, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

    $sorted = [];
    foreach ($parents as $parent) {
        $sorted[] = $parent;
        $children = array_filter($pages, fn($p) => ($p['parent'] ?? '') === $parent['id']);
        usort($children, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        foreach ($children as $child) {
            $sorted[] = $child;
        }
    }
    return $sorted;
}

// ========== EDIT / NEW VIEW ==========
if ($action === 'edit' || $action === 'new'):
    $page = $editId ? find_page($pages, $editId) : null;
    $parentPages = get_parent_pages($pages, $editId);
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?= $page ? 'Pagina bewerken' : 'Nieuwe pagina' ?></h1>
    <a href="/beheer/?tab=paginas" class="btn-admin btn-admin-outline text-sm">&larr; Terug</a>
</div>

<form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Titel <span class="help-tooltip" data-help="De titel van de pagina. Wordt getoond als heading bovenaan de pagina.">?</span></label>
                    <input type="text" name="title" id="page-title" value="<?= e($page['title'] ?? '') ?>" required class="admin-input w-full text-lg">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Slug <span class="help-tooltip" data-help="Het webadres van deze pagina. Wordt automatisch aangemaakt. Pas alleen aan als je een goede reden hebt.">?</span></label>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-sm">/</span>
                        <input type="text" name="slug" id="page-slug" value="<?= e($page['slug'] ?? '') ?>" class="admin-input flex-1" placeholder="wordt-automatisch-ingevuld">
                    </div>
                    <?php if ($page): ?>
                    <p class="text-xs text-yellow-500 mt-1">Let op: het wijzigen van de slug breekt bestaande links. Stel een redirect in.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Content <span class="help-tooltip" data-help="De inhoud van de pagina in HTML. Gebruik &lt;p&gt; voor alinea's, &lt;h2&gt; voor tussenkopjes, &lt;strong&gt; voor vet, &lt;a href=&quot;...&quot;&gt; voor links.">?</span></label>
                    <textarea name="content" rows="15" class="admin-input w-full"><?= e($page['content'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SEO -->
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">SEO</h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">SEO titel <span class="help-tooltip" data-help="Maximaal 60 tekens. Dit is wat Google toont als paginatitel.">?</span></label>
                    <input type="text" name="seo_title" id="seo-title" value="<?= e($page['seo_title'] ?? '') ?>" class="admin-input w-full" maxlength="60">
                    <p class="text-xs text-gray-500 mt-1"><span id="seo-title-count"><?= strlen($page['seo_title'] ?? '') ?></span>/60 tekens</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">SEO omschrijving <span class="help-tooltip" data-help="Maximaal 155 tekens. De beschrijving onder de paginatitel in Google.">?</span></label>
                    <textarea name="seo_description" id="seo-desc" rows="2" class="admin-input w-full" maxlength="155"><?= e($page['seo_description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1"><span id="seo-desc-count"><?= strlen($page['seo_description'] ?? '') ?></span>/155 tekens</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">Publiceren</h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Status <span class="help-tooltip" data-help="Concept-pagina's zijn alleen zichtbaar in het beheerpanel.">?</span></label>
                    <select name="status" class="admin-input w-full">
                        <option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Concept</option>
                        <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Gepubliceerd</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Template <span class="help-tooltip" data-help="Default = standaard pagina. Contact = pagina met contactformulier onderaan.">?</span></label>
                    <select name="template" class="admin-input w-full">
                        <option value="default" <?= ($page['template'] ?? '') === 'default' ? 'selected' : '' ?>>Default</option>
                        <option value="contact" <?= ($page['template'] ?? '') === 'contact' ? 'selected' : '' ?>>Contact</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Parent <span class="help-tooltip" data-help="Maak dit een subpagina van een andere pagina. Laat leeg voor een hoofdpagina.">?</span></label>
                    <select name="parent" id="page-parent" class="admin-input w-full">
                        <option value="">— Geen (hoofdpagina) —</option>
                        <?php foreach ($parentPages as $pp): ?>
                        <option value="<?= e($pp['id']) ?>" <?= ($page['parent'] ?? '') === $pp['id'] ? 'selected' : '' ?>><?= e($pp['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Volgorde</label>
                    <input type="number" name="sort_order" value="<?= (int)($page['sort_order'] ?? 0) ?>" class="admin-input w-full" min="0">
                </div>

                <button type="submit" name="save_page" class="btn-admin btn-admin-primary w-full">
                    <?= $page ? 'Bijwerken' : 'Aanmaken' ?>
                </button>
            </div>

            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">Menu</h3>

                <div class="mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="show_in_menu" value="0">
                        <input type="checkbox" name="show_in_menu" value="1" <?= !empty($page['show_in_menu']) ? 'checked' : '' ?> class="w-4 h-4 rounded">
                        <span class="text-sm text-gray-300">Tonen in menu <span class="help-tooltip" data-help="Vink aan om deze pagina automatisch in het hoofdmenu te tonen.">?</span></span>
                    </label>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Menu label <span class="help-tooltip" data-help="De tekst die in het menu verschijnt. Laat leeg om de paginatitel te gebruiken.">?</span></label>
                    <input type="text" name="menu_label" value="<?= e($page['menu_label'] ?? '') ?>" class="admin-input w-full" placeholder="Paginatitel wordt gebruikt">
                </div>
            </div>

            <div class="admin-card">
                <h3 class="text-md font-semibold text-white mb-3">Afbeelding</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Uitgelichte afbeelding <span class="help-tooltip" data-help="Een optionele uitgelichte afbeelding voor deze pagina.">?</span></label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="image" id="page-image" value="<?= e($page['image'] ?? '') ?>" class="admin-input flex-1" placeholder="/images/uploads/...">
                        <button type="button" onclick="openMediaPicker('page-image')" class="btn-admin-sm">Kies</button>
                    </div>
                    <?php if (!empty($page['image'])): ?>
                    <img src="<?= e($page['image']) ?>" class="mt-2 w-full h-32 object-cover rounded" alt="">
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($page): ?>
            <div class="admin-card">
                <p class="text-xs text-gray-500">ID: <?= e($page['id']) ?></p>
                <p class="text-xs text-gray-500 mt-1">Aangemaakt: <?= e($page['created_at'] ?? '') ?></p>
                <p class="text-xs text-gray-500 mt-1">Bijgewerkt: <?= e($page['updated_at'] ?? '') ?></p>
                <?php if ($page['status'] === 'published'): ?>
                <a href="/<?= e($page['slug']) ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-300 mt-2 inline-block">Bekijk pagina &rarr;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
// Auto-generate slug from title
var titleInput = document.getElementById('page-title');
var slugInput = document.getElementById('page-slug');
var isNewPage = <?= $page ? 'false' : 'true' ?>;
var slugManuallyEdited = false;

if (isNewPage) {
    slugInput.addEventListener('input', function() {
        slugManuallyEdited = this.value !== '';
    });

    titleInput.addEventListener('input', function() {
        if (!slugManuallyEdited || slugInput.value === '') {
            var text = this.value.toLowerCase()
                .replace(/[áàäâ]/g, 'a').replace(/[éèëê]/g, 'e')
                .replace(/[íìïî]/g, 'i').replace(/[óòöô]/g, 'o')
                .replace(/[úùüû]/g, 'u').replace(/[ñ]/g, 'n').replace(/[ç]/g, 'c')
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            slugInput.value = text;
        }
    });
}

// SEO character counters
document.getElementById('seo-title').addEventListener('input', function() {
    document.getElementById('seo-title-count').textContent = this.value.length;
});
document.getElementById('seo-desc').addEventListener('input', function() {
    document.getElementById('seo-desc-count').textContent = this.value.length;
});
</script>

<?php
// ========== LIST VIEW ==========
else:
    $sortedPages = sort_pages_hierarchically($pages);
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Pagina's</h1>
    <a href="/beheer/?tab=paginas&action=new" class="btn-admin btn-admin-primary text-sm">+ Nieuwe pagina</a>
</div>

<?php if (empty($pages)): ?>
<div class="admin-card">
    <p class="text-gray-400">Nog geen pagina's aangemaakt. Maak je eerste pagina aan.</p>
</div>
<?php else: ?>
<div class="admin-card overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-gray-400 border-b border-gray-700">
                <th class="pb-3 font-medium">Titel</th>
                <th class="pb-3 font-medium">Slug</th>
                <th class="pb-3 font-medium">Status</th>
                <th class="pb-3 font-medium">Menu</th>
                <th class="pb-3 font-medium">Volgorde</th>
                <th class="pb-3 font-medium text-right">Acties</th>
            </tr>
        </thead>
        <tbody class="text-gray-300">
            <?php foreach ($sortedPages as $p): ?>
            <tr class="border-b border-gray-800 hover:bg-gray-800/50">
                <td class="py-3">
                    <?php if (!empty($p['parent'])): ?>
                        <span class="text-gray-600 mr-1">↳</span>
                    <?php endif; ?>
                    <a href="/beheer/?tab=paginas&action=edit&id=<?= e($p['id']) ?>" class="text-blue-400 hover:text-blue-300 font-medium">
                        <?= e($p['title']) ?>
                    </a>
                </td>
                <td class="py-3 text-gray-500">/<?= e($p['slug']) ?></td>
                <td class="py-3">
                    <?php if ($p['status'] === 'published'): ?>
                        <span class="inline-block px-2 py-0.5 bg-green-900/50 text-green-400 text-xs rounded">Gepubliceerd</span>
                    <?php else: ?>
                        <span class="inline-block px-2 py-0.5 bg-yellow-900/50 text-yellow-400 text-xs rounded">Concept</span>
                    <?php endif; ?>
                </td>
                <td class="py-3">
                    <?php if ($p['show_in_menu']): ?>
                        <span class="text-green-400">&#10003;</span>
                    <?php else: ?>
                        <span class="text-gray-600">—</span>
                    <?php endif; ?>
                </td>
                <td class="py-3 text-gray-500"><?= (int)($p['sort_order'] ?? 0) ?></td>
                <td class="py-3 text-right">
                    <a href="/beheer/?tab=paginas&action=edit&id=<?= e($p['id']) ?>" class="text-blue-400 hover:text-blue-300 text-xs mr-3">Bewerken</a>
                    <form method="POST" class="inline" onsubmit="return confirm('Weet je zeker dat je deze pagina wilt verwijderen?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_id" value="<?= e($p['id']) ?>">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-500 mr-2">
                            <input type="checkbox" name="create_redirect" value="1" class="w-3 h-3"> Redirect
                        </label>
                        <button type="submit" name="delete_page" class="text-red-400 hover:text-red-300 text-xs">Verwijderen</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>
