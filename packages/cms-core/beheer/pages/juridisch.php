<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Legal text editor with tabs
 */
require_once EASEO_ROOT . '/includes/legal.php';
$legal = ContentRepository::loadJson('legal.json');
$activeSection = $_GET['sectie'] ?? 'privacy';
$sections = ['privacy' => t('legal_section_privacy'), 'voorwaarden' => t('legal_section_terms'), 'cookies' => t('legal_section_cookies')];
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_legal'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $section = $_POST['section'] ?? '';
        if (isset($sections[$section])) {
            if (!isset($legal[$section]) || !is_array($legal[$section])) {
                $legal[$section] = ['seo_title' => '', 'seo_description' => '', 'content' => '', 'last_updated' => ''];
            }
            $legal[$section]['content'] = $_POST['content'] ?? '';
            $legal[$section]['last_updated'] = date('Y-m-d H:i:s');
            ContentRepository::saveJson('legal.json', $legal);
            audit_log('juridisch_bewerkt', "Sectie: {$sections[$section]}");
            $_SESSION['flash_success'] = t('success_legal_saved');
        }
    }
    header('Location: /beheer/?tab=juridisch&sectie=' . urlencode($activeSection));
    exit;
}
// Handle reset to default
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_legal'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $section = $_POST['section'] ?? '';
        if (isset($sections[$section])) {
            if (!isset($legal[$section]) || !is_array($legal[$section])) {
                $legal[$section] = ['seo_title' => '', 'seo_description' => '', 'content' => '', 'last_updated' => ''];
            }
            $legal[$section]['content'] = '';
            ContentRepository::saveJson('legal.json', $legal);
            $_SESSION['flash_success'] = t('success_legal_reset');
        }
    }
    header('Location: /beheer/?tab=juridisch&sectie=' . urlencode($activeSection));
    exit;
}
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo t('legal_title');
?></h1>

<!-- Section tabs -->
<div class="admin-tabs">
    <?php 
foreach ($sections as $key => $label) {
    ?>
    <a href="/beheer/?tab=juridisch&sectie=<?php 
    echo $key;
    ?>"
       class="admin-tab <?php 
    echo $activeSection === $key ? 'active' : '';
    ?>">
        <?php 
    echo $label;
    ?>
    </a>
    <?php 
}
?>
</div>

<form method="POST" class="admin-card">
    <?php 
echo csrf_field();
?>
    <input type="hidden" name="section" value="<?php 
echo ContentRepository::escape($activeSection);
?>">

    <div class="mb-2 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-white"><?php 
echo ContentRepository::escape($sections[$activeSection] ?? '');
?></h2>
        <button type="submit" name="reset_legal" class="text-sm text-gray-500 hover:text-red-400"
                onclick="return confirm('<?php 
echo t('confirm_restore_legal');
?>')">
            <?php 
echo t('button_restore_default_text');
?> <span class="help-tooltip" data-help="<?php 
echo t('tooltip_restore_legal');
?>">?</span>
        </button>
    </div>

    <p class="text-xs text-gray-500 mb-4">
        <?php 
echo t('legal_available_variables');
?> <code>{bedrijfsnaam}</code> <code>{email}</code> <code>{telefoon}</code>
        <code>{adres}</code> <code>{postcode}</code> <code>{plaats}</code> <code>{kvk}</code>
        <code>{btw}</code> <code>{website}</code> <code>{datum}</code>
    </p>

    <?php 
$currentContent = '';
if (isset($legal[$activeSection])) {
    $currentContent = is_array($legal[$activeSection]) ? $legal[$activeSection]['content'] ?? '' : $legal[$activeSection];
}
?>
    <textarea name="content" rows="20" class="admin-input w-full font-mono text-sm"><?php 
echo ContentRepository::escape($currentContent);
?></textarea>

    <p class="text-xs text-gray-500 mt-2"><?php 
echo t('hint_legal_empty');
?></p>

    <div class="flex justify-end mt-4 pt-4 border-t border-gray-700">
        <button type="submit" name="save_legal" class="btn-admin btn-admin-primary"><?php 
echo t('button_save');
?></button>
    </div>
</form>
<?php 
