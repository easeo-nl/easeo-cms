<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Page content editor with auto field config
 */
$pages = ContentRepository::loadJson('content.json');
$currentPage = $_GET['pagina'] ?? array_key_first($pages) ?? 'home';
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $pageName = $_POST['page_name'] ?? '';
        if (isset($pages[$pageName])) {
            foreach ($pages[$pageName] as $key => $oldValue) {
                if (isset($_POST['fields'][$key])) {
                    $val = sanitize_input($_POST['fields'][$key]);
                    // Strip HTML from SEO fields
                    if (str_starts_with($key, 'meta_')) {
                        $val = strip_tags($val);
                    }
                    $pages[$pageName][$key] = $val;
                }
            }
            ContentRepository::saveJson('content.json', $pages);
            // Reload
            $content = $pages;
            audit_log('content_bewerkt', "Pagina: {$pageName}");
            $_SESSION['flash_success'] = Translator::translate('success_content_saved');
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}
// Handle add new page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $newSlug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['new_page_slug'] ?? '')));
        if ($newSlug && !isset($pages[$newSlug])) {
            $pages[$newSlug] = ['meta_title' => ucfirst($newSlug), 'meta_description' => '', 'titel' => ucfirst($newSlug), 'intro_tekst' => '', 'inhoud_tekst' => '', 'afbeelding' => ''];
            ContentRepository::saveJson('content.json', $pages);
            audit_log('pagina_toegevoegd', "Pagina: {$newSlug}");
            $_SESSION['flash_success'] = Translator::translate('success_page_added');
            $currentPage = $newSlug;
        } else {
            $_SESSION['flash_error'] = Translator::translate('error_invalid_slug_or_exists');
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}
// Handle delete page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_page'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $delPage = $_POST['page_name'] ?? '';
        $protected = ['home', 'over', 'contact'];
        if (isset($pages[$delPage]) && !in_array($delPage, $protected)) {
            unset($pages[$delPage]);
            ContentRepository::saveJson('content.json', $pages);
            audit_log('pagina_verwijderd', "Pagina: {$delPage}");
            $_SESSION['flash_success'] = Translator::translate('success_page_deleted');
            $currentPage = 'home';
        } else {
            $_SESSION['flash_error'] = Translator::translate('error_page_cannot_delete');
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}
// Handle add field
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_field'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $pageName = $_POST['page_name'] ?? '';
        $fieldKey = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['new_field_key'] ?? '')));
        if ($pageName && $fieldKey && isset($pages[$pageName]) && !isset($pages[$pageName][$fieldKey])) {
            $pages[$pageName][$fieldKey] = '';
            ContentRepository::saveJson('content.json', $pages);
            $_SESSION['flash_success'] = Translator::translate('success_field_added');
        }
    }
    header('Location: /beheer/?tab=content&pagina=' . urlencode($currentPage));
    exit;
}
// Reload after potential changes
$pages = ContentRepository::loadJson('content.json');
$pageData = $pages[$currentPage] ?? [];
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo Translator::translate('content_edit_title');
?></h1>
</div>

<!-- Page tabs -->
<div class="flex flex-wrap items-center gap-2 mb-6">
    <?php 
foreach (array_keys($pages) as $slug) {
    ?>
        <a href="/beheer/?tab=content&pagina=<?php 
    echo ContentRepository::escape($slug);
    ?>"
           class="px-3 py-1.5 rounded text-sm <?php 
    echo $slug === $currentPage ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600';
    ?>">
            <?php 
    echo ContentRepository::escape(ucfirst($slug));
    ?>
        </a>
    <?php 
}
?>

    <!-- Add page button -->
    <form method="POST" class="flex items-center gap-2 ml-4">
        <?php 
echo csrf_field();
?>
        <input type="text" name="new_page_slug" placeholder="<?php 
echo Translator::translate('placeholder_new_page_slug');
?>" class="admin-input text-sm py-1 w-36">
        <button type="submit" name="add_page" class="btn-admin-sm"><?php 
echo Translator::translate('button_add_page');
?></button>
    </form>
</div>

<?php 
if ($pageData) {
    ?>
<!-- Content form -->
<form method="POST" class="admin-card">
    <?php 
    echo csrf_field();
    ?>
    <input type="hidden" name="page_name" value="<?php 
    echo ContentRepository::escape($currentPage);
    ?>">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-white"><?php 
    echo ContentRepository::escape(ucfirst($currentPage));
    ?></h2>
        <?php 
    if (!in_array($currentPage, ['home', 'over', 'contact'])) {
        ?>
        <button type="submit" name="delete_page" class="btn-admin btn-admin-danger text-sm"
                onclick="return confirm('<?php 
        echo Translator::translate('confirm_delete_page');
        ?>')">
            <?php 
        echo Translator::translate('button_delete');
        ?>
        </button>
        <?php 
    }
    ?>
    </div>

    <?php 
    foreach ($pageData as $key => $value) {
        $config = auto_field_config($key, $value);
        echo render_field($config, $value, 'fields');
    }
    ?>

    <!-- Add field -->
    <div class="border-t border-gray-700 pt-4 mt-4 mb-4">
        <div class="flex items-center gap-2">
            <input type="text" name="new_field_key" placeholder="<?php 
    echo Translator::translate('placeholder_new_field_key');
    ?>" class="admin-input text-sm py-1 w-48">
            <button type="submit" name="add_field" class="btn-admin-sm text-xs"><?php 
    echo Translator::translate('button_add_field');
    ?></button>
        </div>
        <p class="text-xs text-gray-500 mt-1"><?php 
    echo Translator::translate('hint_field_naming');
    ?></p>
    </div>

    <div class="flex justify-end pt-4 border-t border-gray-700">
        <button type="submit" name="save_content" class="btn-admin btn-admin-primary"><?php 
    echo Translator::translate('button_save');
    ?></button>
    </div>
</form>
<?php 
} else {
    ?>
<div class="admin-card">
    <p class="text-gray-400"><?php 
    echo Translator::translate('error_page_not_found');
    ?></p>
</div>
<?php 
}
