<?php
/**
 * EASEO CMS — Menu editor (main + footer)
 */
$nav = load_json('navigation.json');

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nav'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $menuType = $_POST['menu_type'] ?? 'main';

        $labels = $_POST['label'] ?? [];
        $urls = $_POST['url'] ?? [];
        $items = [];

        for ($i = 0; $i < count($labels); $i++) {
            $label = trim($labels[$i] ?? '');
            $url = trim($urls[$i] ?? '');
            if (empty($label)) continue;

            // Sanitize: strip HTML from label, validate URL structure
            $label = strip_tags($label);
            $url = strip_tags($url);
            $item = ['label' => $label, 'url' => $url];
            if ($menuType === 'main') {
                $item['children'] = [];
                // Parse children
                $childLabels = $_POST['child_label'][$i] ?? [];
                $childUrls = $_POST['child_url'][$i] ?? [];
                for ($j = 0; $j < count($childLabels); $j++) {
                    $cl = trim($childLabels[$j] ?? '');
                    $cu = trim($childUrls[$j] ?? '');
                    if (empty($cl)) continue;
                    $item['children'][] = ['label' => strip_tags($cl), 'url' => strip_tags($cu)];
                }
            }
            $items[] = $item;
        }

        $nav[$menuType] = $items;
        save_json('navigation.json', $nav);
        audit_log('navigatie_bewerkt', "Menu: {$menuType}");
        $_SESSION['flash_success'] = t('success_navigation_saved');
    }
    header('Location: /beheer/?tab=navigatie&menu=' . urlencode($_POST['menu_type'] ?? 'main'));
    exit;
}

$activeMenu = $_GET['menu'] ?? 'main';
$menuItems = $nav[$activeMenu] ?? [];
?>

<h1 class="text-2xl font-bold text-white mb-6"><?= t('navigation_title') ?></h1>

<p class="text-sm text-gray-400 mb-4">
    <strong><?= t('nav_label_heading') ?></strong> <span class="help-tooltip" data-help="<?= t('tooltip_nav_label') ?>">?</span> &mdash;
    <strong><?= t('nav_url_heading') ?></strong> <span class="help-tooltip" data-help="<?= t('tooltip_nav_url') ?>">?</span>
</p>

<!-- Menu tabs -->
<div class="admin-tabs">
    <a href="/beheer/?tab=navigatie&menu=main" class="admin-tab <?= $activeMenu === 'main' ? 'active' : '' ?>"><?= t('nav_main_menu_tab') ?></a>
    <a href="/beheer/?tab=navigatie&menu=footer" class="admin-tab <?= $activeMenu === 'footer' ? 'active' : '' ?>"><?= t('nav_footer_menu_tab') ?></a>
</div>

<form method="POST" class="admin-card" id="nav-form">
    <?= csrf_field() ?>
    <input type="hidden" name="menu_type" value="<?= e($activeMenu) ?>">

    <div id="menu-items">
        <?php foreach ($menuItems as $i => $item): ?>
        <div class="menu-item border border-gray-700 rounded-lg p-4 mb-3">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-gray-600 cursor-grab">&#9776;</span>
                <input type="text" name="label[]" value="<?= e($item['label'] ?? '') ?>" placeholder="Label" class="admin-input flex-1" title="<?= t('tooltip_nav_label') ?>">
                <input type="text" name="url[]" value="<?= e($item['url'] ?? '') ?>" placeholder="URL" class="admin-input flex-1" title="<?= t('tooltip_nav_url') ?>">
                <button type="button" onclick="removeMenuItem(this)" class="text-red-400 hover:text-red-300 text-lg">&times;</button>
            </div>

            <?php if ($activeMenu === 'main'): ?>
            <div class="ml-8 mt-2 space-y-2 children-container">
                <?php foreach (($item['children'] ?? []) as $j => $child): ?>
                <div class="child-item flex items-center gap-3">
                    <span class="text-gray-700 text-sm">↳</span>
                    <input type="text" name="child_label[<?= $i ?>][]" value="<?= e($child['label'] ?? '') ?>" placeholder="Sub-label" class="admin-input flex-1 text-sm">
                    <input type="text" name="child_url[<?= $i ?>][]" value="<?= e($child['url'] ?? '') ?>" placeholder="Sub-URL" class="admin-input flex-1 text-sm">
                    <button type="button" onclick="this.closest('.child-item').remove()" class="text-red-400 text-sm">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addChild(this, <?= $i ?>)" class="ml-8 mt-2 text-xs text-blue-400 hover:text-blue-300"><?= t('button_add_sub_item') ?></button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-3 mt-4">
        <button type="button" onclick="addMenuItem()" class="btn-admin btn-admin-outline text-sm"><?= t('button_add_menu_item') ?></button>
        <button type="submit" name="save_nav" class="btn-admin btn-admin-primary"><?= t('button_save') ?></button>
    </div>
</form>

<?php
// Show auto menu items from pages.json
$pagesData = load_json('pages.json');
$autoMenuPages = array_filter($pagesData['pages'] ?? [], fn($p) => !empty($p['show_in_menu']) && $p['status'] === 'published');
if (!empty($autoMenuPages)):
?>
<div class="admin-card mt-6">
    <h3 class="text-md font-semibold text-white mb-2"><?= t('nav_auto_items_heading') ?></h3>
    <p class="text-sm text-gray-400 mb-3"><?= t('nav_auto_items_desc') ?></p>
    <ul class="space-y-1">
        <?php foreach ($autoMenuPages as $ap): ?>
        <li class="flex items-center gap-2 text-sm text-gray-300">
            <span class="text-green-400">&#10003;</span>
            <?= e($ap['menu_label'] ?: $ap['title']) ?>
            <span class="text-gray-600">/<?= e($ap['slug']) ?></span>
            <a href="/beheer/?tab=paginas&action=edit&id=<?= e($ap['id']) ?>" class="text-blue-400 hover:text-blue-300 text-xs ml-auto"><?= t('action_edit') ?></a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<script>
var menuItemCount = <?= count($menuItems) ?>;
var isHoofdmenu = <?= $activeMenu === 'main' ? 'true' : 'false' ?>;

function addMenuItem() {
    var container = document.getElementById('menu-items');
    var div = document.createElement('div');
    div.className = 'menu-item border border-gray-700 rounded-lg p-4 mb-3';
    var html = '<div class="flex items-center gap-3 mb-2">' +
        '<span class="text-gray-600 cursor-grab">&#9776;</span>' +
        '<input type="text" name="label[]" placeholder="Label" class="admin-input flex-1">' +
        '<input type="text" name="url[]" placeholder="URL" class="admin-input flex-1">' +
        '<button type="button" onclick="removeMenuItem(this)" class="text-red-400 hover:text-red-300 text-lg">&times;</button>' +
        '</div>';
    if (isHoofdmenu) {
        html += '<div class="ml-8 mt-2 space-y-2 children-container"></div>' +
            '<button type="button" onclick="addChild(this, ' + menuItemCount + ')" class="ml-8 mt-2 text-xs text-blue-400 hover:text-blue-300"><?= t('button_add_sub_item') ?></button>';
    }
    div.innerHTML = html;
    container.appendChild(div);
    menuItemCount++;
}

function removeMenuItem(btn) {
    btn.closest('.menu-item').remove();
    reindexChildren();
}

function addChild(btn, parentIdx) {
    var container = btn.previousElementSibling;
    var div = document.createElement('div');
    div.className = 'child-item flex items-center gap-3';
    div.innerHTML = '<span class="text-gray-700 text-sm">↳</span>' +
        '<input type="text" name="child_label[' + parentIdx + '][]" placeholder="Sub-label" class="admin-input flex-1 text-sm">' +
        '<input type="text" name="child_url[' + parentIdx + '][]" placeholder="Sub-URL" class="admin-input flex-1 text-sm">' +
        '<button type="button" onclick="this.closest(\'.child-item\').remove()" class="text-red-400 text-sm">&times;</button>';
    container.appendChild(div);
}

function reindexChildren() {
    document.querySelectorAll('.menu-item').forEach(function(item, idx) {
        item.querySelectorAll('[name^="child_label"]').forEach(function(input) {
            input.name = 'child_label[' + idx + '][]';
        });
        item.querySelectorAll('[name^="child_url"]').forEach(function(input) {
            input.name = 'child_url[' + idx + '][]';
        });
        var addBtn = item.querySelector('button[onclick*="addChild"]');
        if (addBtn) addBtn.setAttribute('onclick', 'addChild(this, ' + idx + ')');
    });
}
</script>
