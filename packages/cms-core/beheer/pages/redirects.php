<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Redirect management
 */
$redirectData = ContentRepository::loadJson('redirects.json');
$redirects = $redirectData['redirects'] ?? [];
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_redirects'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $vans = $_POST['van'] ?? [];
        $naars = $_POST['naar'] ?? [];
        $types = $_POST['type'] ?? [];
        $newRedirects = [];
        for ($i = 0; $i < count($vans); $i++) {
            $van = trim($vans[$i] ?? '');
            $naar = trim($naars[$i] ?? '');
            if (empty($van) || empty($naar)) {
                continue;
            }
            $newRedirects[] = ['van' => $van, 'naar' => $naar, 'type' => ($types[$i] ?? '301') === '302' ? '302' : '301'];
        }
        ContentRepository::saveJson('redirects.json', ['redirects' => $newRedirects]);
        audit_log('redirects_bewerkt', count($newRedirects) . ' redirects');
        $_SESSION['flash_success'] = t('success_redirects_saved');
    }
    header('Location: /beheer/?tab=redirects');
    exit;
}
$redirectData = ContentRepository::loadJson('redirects.json');
$redirects = $redirectData['redirects'] ?? [];
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo t('redirects_title');
?></h1>

<p class="text-sm text-gray-400 mb-4">
    <strong><?php 
echo t('redirect_from_label');
?></strong> <span class="help-tooltip" data-help="<?php 
echo t('tooltip_redirect_from');
?>">?</span> &rarr;
    <strong><?php 
echo t('redirect_to_label');
?></strong> <span class="help-tooltip" data-help="<?php 
echo t('tooltip_redirect_to');
?>">?</span>
</p>

<form method="POST" class="admin-card">
    <?php 
echo csrf_field();
?>

    <div id="redirect-rows">
        <?php 
if (empty($redirects)) {
    $redirects = [['van' => '', 'naar' => '', 'type' => '301']];
}
?>
        <?php 
foreach ($redirects as $r) {
    ?>
        <div class="redirect-row grid grid-cols-12 gap-3 mb-3 items-end">
            <div class="col-span-4">
                <input type="text" name="van[]" value="<?php 
    echo ContentRepository::escape($r['van'] ?? '');
    ?>" class="admin-input w-full" placeholder="/oud-pad">
            </div>
            <div class="col-span-1 text-center text-gray-500">&rarr;</div>
            <div class="col-span-4">
                <input type="text" name="naar[]" value="<?php 
    echo ContentRepository::escape($r['naar'] ?? '');
    ?>" class="admin-input w-full" placeholder="/nieuw-pad">
            </div>
            <div class="col-span-2">
                <select name="type[]" class="admin-input w-full">
                    <option value="301" <?php 
    echo ($r['type'] ?? '') === '301' ? 'selected' : '';
    ?>><?php 
    echo t('redirect_type_301');
    ?></option>
                    <option value="302" <?php 
    echo ($r['type'] ?? '') === '302' ? 'selected' : '';
    ?>><?php 
    echo t('redirect_type_302');
    ?></option>
                </select>
            </div>
            <div class="col-span-1">
                <button type="button" onclick="this.closest('.redirect-row').remove()" class="text-red-400 hover:text-red-300">&times;</button>
            </div>
        </div>
        <?php 
}
?>
    </div>

    <div class="flex items-center gap-3 mt-4">
        <button type="button" onclick="addRedirect()" class="btn-admin btn-admin-outline text-sm"><?php 
echo t('button_add_redirect');
?></button>
        <button type="submit" name="save_redirects" class="btn-admin btn-admin-primary"><?php 
echo t('button_save');
?></button>
    </div>
</form>

<div class="admin-card mt-6">
    <h3 class="text-md font-semibold text-white mb-2"><?php 
echo t('redirect_info_heading');
?></h3>
    <p class="text-sm text-gray-400"><?php 
echo t('redirect_info_text');
?></p>
</div>

<script>
function addRedirect() {
    var container = document.getElementById('redirect-rows');
    var div = document.createElement('div');
    div.className = 'redirect-row grid grid-cols-12 gap-3 mb-3 items-end';
    div.innerHTML = '<div class="col-span-4"><input type="text" name="van[]" class="admin-input w-full" placeholder="/oud-pad"></div>' +
        '<div class="col-span-1 text-center text-gray-500">&rarr;</div>' +
        '<div class="col-span-4"><input type="text" name="naar[]" class="admin-input w-full" placeholder="/nieuw-pad"></div>' +
        '<div class="col-span-2"><select name="type[]" class="admin-input w-full"><option value="301"><?php 
echo t('redirect_type_301');
?></option><option value="302"><?php 
echo t('redirect_type_302');
?></option></select></div>' +
        '<div class="col-span-1"><button type="button" onclick="this.closest(\'.redirect-row\').remove()" class="text-red-400 hover:text-red-300">&times;</button></div>';
    container.appendChild(div);
}
</script>
<?php 
