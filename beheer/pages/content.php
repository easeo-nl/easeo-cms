<?php
/**
 * EASEO CMS — Page content editor with auto field config
 */
$pages = load_json('content.json');
$currentPage = $_GET['pagina'] ?? array_key_first($pages) ?? 'home';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $pageName = $_POST['page_name'] ?? '';
        if (isset($pages[$pageName])) {
            foreach ($pages[$pageName] as $key => $oldValue) {
                if (isset($_POST['fields'][$key])) {
                    $pages[$pageName][$key] = sanitize_input($_POST['fields'][$key]);
                }
            }
            save_json('content.json', $pages);
            // Reload
            $content = $pages;
            audit_log('content_bewerkt', "Pagina: {$pageName}");
            $_SESSION['flash_success'] = 'Content opgeslagen.';
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}

// Handle add new page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $newSlug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['new_page_slug'] ?? '')));
        if ($newSlug && !isset($pages[$newSlug])) {
            $pages[$newSlug] = [
                'meta_title' => ucfirst($newSlug),
                'meta_description' => '',
                'titel' => ucfirst($newSlug),
                'intro_tekst' => '',
                'inhoud_tekst' => '',
                'afbeelding' => '',
            ];
            save_json('content.json', $pages);
            audit_log('pagina_toegevoegd', "Pagina: {$newSlug}");
            $_SESSION['flash_success'] = 'Pagina toegevoegd.';
            $currentPage = $newSlug;
        } else {
            $_SESSION['flash_error'] = 'Ongeldige slug of pagina bestaat al.';
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}

// Handle delete page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $delPage = $_POST['page_name'] ?? '';
        $protected = ['home', 'over', 'contact'];
        if (isset($pages[$delPage]) && !in_array($delPage, $protected)) {
            unset($pages[$delPage]);
            save_json('content.json', $pages);
            audit_log('pagina_verwijderd', "Pagina: {$delPage}");
            $_SESSION['flash_success'] = 'Pagina verwijderd.';
            $currentPage = 'home';
        } else {
            $_SESSION['flash_error'] = 'Deze pagina kan niet worden verwijderd.';
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}

// Handle add field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_field'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $pageName = $_POST['page_name'] ?? '';
        $fieldKey = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['new_field_key'] ?? '')));
        if ($pageName && $fieldKey && isset($pages[$pageName]) && !isset($pages[$pageName][$fieldKey])) {
            $pages[$pageName][$fieldKey] = '';
            save_json('content.json', $pages);
            $_SESSION['flash_success'] = 'Veld toegevoegd.';
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}

// Reload after potential changes
$pages = load_json('content.json');
$pageData = $pages[$currentPage] ?? [];
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Content bewerken</h1>
</div>

<!-- Page tabs -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <?php foreach (array_keys($pages) as $slug): ?>
        <a href="/beheer/?tab=content&pagina=<?= e($slug) ?>"
           class="px-3 py-1.5 rounded text-sm <?= $slug === $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' ?>">
            <?= e(ucfirst($slug)) ?>
        </a>
    <?php endforeach; ?>

    <!-- Add page button -->
    <form method="POST" class="flex items-center gap-2 ml-4">
        <?= csrf_field() ?>
        <input type="text" name="new_page_slug" placeholder="nieuwe-pagina" class="admin-input text-sm py-1 w-36">
        <button type="submit" name="add_page" class="btn-admin-sm">+ Pagina</button>
    </form>
</div>

<?php if ($pageData): ?>
<!-- Content form -->
<form method="POST" class="admin-card">
    <?= csrf_field() ?>
    <input type="hidden" name="page_name" value="<?= e($currentPage) ?>">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-white"><?= e(ucfirst($currentPage)) ?></h2>
        <?php if (!in_array($currentPage, ['home', 'over', 'contact'])): ?>
        <button type="submit" name="delete_page" class="btn-admin btn-admin-danger text-sm"
                onclick="return confirm('Weet u zeker dat u deze pagina wilt verwijderen?')">
            Verwijderen
        </button>
        <?php endif; ?>
    </div>

    <?php foreach ($pageData as $key => $value):
        $config = auto_field_config($key, $value);
        echo render_field($config, $value, 'fields');
    endforeach; ?>

    <!-- Add field -->
    <div class="border-t border-gray-700 pt-4 mt-4 mb-4">
        <div class="flex items-center gap-2">
            <input type="text" name="new_field_key" placeholder="nieuw_veld_naam" class="admin-input text-sm py-1 w-48">
            <button type="submit" name="add_field" class="btn-admin-sm text-xs">+ Veld</button>
        </div>
        <p class="text-xs text-gray-500 mt-1">Gebruik underscores: bijv. sectie3_tekst, banner_afbeelding</p>
    </div>

    <div class="flex justify-end pt-4 border-t border-gray-700">
        <button type="submit" name="save_content" class="btn-admin btn-admin-primary">Opslaan</button>
    </div>
</form>
<?php else: ?>
<div class="admin-card">
    <p class="text-gray-400">Pagina niet gevonden.</p>
</div>
<?php endif; ?>
