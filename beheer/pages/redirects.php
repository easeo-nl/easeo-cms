<?php
/**
 * EASEO CMS — Redirect management
 */
$redirectData = load_json('redirects.json');
$redirects = $redirectData['redirects'] ?? [];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_redirects'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $vans = $_POST['van'] ?? [];
        $naars = $_POST['naar'] ?? [];
        $types = $_POST['type'] ?? [];
        $newRedirects = [];

        for ($i = 0; $i < count($vans); $i++) {
            $van = trim($vans[$i] ?? '');
            $naar = trim($naars[$i] ?? '');
            if (empty($van) || empty($naar)) continue;

            $newRedirects[] = [
                'van' => $van,
                'naar' => $naar,
                'type' => ($types[$i] ?? '301') === '302' ? '302' : '301',
            ];
        }

        save_json('redirects.json', ['redirects' => $newRedirects]);
        audit_log('redirects_bewerkt', count($newRedirects) . ' redirects');
        $_SESSION['flash_success'] = 'Redirects opgeslagen.';
    }
    header('Location: /beheer/?tab=redirects');
    exit;
}

$redirectData = load_json('redirects.json');
$redirects = $redirectData['redirects'] ?? [];
?>

<h1 class="text-2xl font-bold text-white mb-6">Redirects</h1>

<p class="text-sm text-gray-400 mb-4">
    <strong>Oud adres</strong> <span class="help-tooltip" data-help="Het oude webadres dat niet meer bestaat. Begint met /. Voorbeeld: /oude-pagina">?</span> &rarr;
    <strong>Nieuw adres</strong> <span class="help-tooltip" data-help="Het nieuwe webadres waar bezoekers naartoe gestuurd worden. Voorbeeld: /nieuwe-pagina">?</span>
</p>

<form method="POST" class="admin-card">
    <?= csrf_field() ?>

    <div id="redirect-rows">
        <?php if (empty($redirects)): $redirects = [['van' => '', 'naar' => '', 'type' => '301']]; endif; ?>
        <?php foreach ($redirects as $r): ?>
        <div class="redirect-row grid grid-cols-12 gap-3 mb-3 items-end">
            <div class="col-span-4">
                <input type="text" name="van[]" value="<?= e($r['van'] ?? '') ?>" class="admin-input w-full" placeholder="/oud-pad">
            </div>
            <div class="col-span-1 text-center text-gray-500">&rarr;</div>
            <div class="col-span-4">
                <input type="text" name="naar[]" value="<?= e($r['naar'] ?? '') ?>" class="admin-input w-full" placeholder="/nieuw-pad">
            </div>
            <div class="col-span-2">
                <select name="type[]" class="admin-input w-full">
                    <option value="301" <?= ($r['type'] ?? '') === '301' ? 'selected' : '' ?>>301 Permanent</option>
                    <option value="302" <?= ($r['type'] ?? '') === '302' ? 'selected' : '' ?>>302 Tijdelijk</option>
                </select>
            </div>
            <div class="col-span-1">
                <button type="button" onclick="this.closest('.redirect-row').remove()" class="text-red-400 hover:text-red-300">&times;</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="flex items-center gap-3 mt-4">
        <button type="button" onclick="addRedirect()" class="btn-admin btn-admin-outline text-sm">+ Redirect</button>
        <button type="submit" name="save_redirects" class="btn-admin btn-admin-primary">Opslaan</button>
    </div>
</form>

<div class="admin-card mt-6">
    <h3 class="text-md font-semibold text-white mb-2">Info</h3>
    <p class="text-sm text-gray-400">Redirects worden verwerkt via PHP (niet via .htaccess). Gebruik relatieve paden, bijv. <code class="bg-gray-800 px-1 rounded">/oud-pad</code> → <code class="bg-gray-800 px-1 rounded">/nieuw-pad</code>.</p>
</div>

<script>
function addRedirect() {
    var container = document.getElementById('redirect-rows');
    var div = document.createElement('div');
    div.className = 'redirect-row grid grid-cols-12 gap-3 mb-3 items-end';
    div.innerHTML = '<div class="col-span-4"><input type="text" name="van[]" class="admin-input w-full" placeholder="/oud-pad"></div>' +
        '<div class="col-span-1 text-center text-gray-500">&rarr;</div>' +
        '<div class="col-span-4"><input type="text" name="naar[]" class="admin-input w-full" placeholder="/nieuw-pad"></div>' +
        '<div class="col-span-2"><select name="type[]" class="admin-input w-full"><option value="301">301 Permanent</option><option value="302">302 Tijdelijk</option></select></div>' +
        '<div class="col-span-1"><button type="button" onclick="this.closest(\'.redirect-row\').remove()" class="text-red-400 hover:text-red-300">&times;</button></div>';
    container.appendChild(div);
}
</script>
