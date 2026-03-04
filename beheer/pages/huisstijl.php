<?php
/**
 * EASEO CMS — Brand editor (colors, fonts, logo)
 */
$siteData = load_json('site.json');

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $siteData['brand']['logo'] = sanitize_input($_POST['logo'] ?? '');
        $siteData['brand']['favicon'] = sanitize_input($_POST['favicon'] ?? '');

        $siteData['brand']['color_primary'] = $_POST['color_primary'] ?? '#2563EB';
        $siteData['brand']['color_secondary'] = $_POST['color_secondary'] ?? '#EA580C';
        $siteData['brand']['color_dark'] = $_POST['color_dark'] ?? '#111827';
        $siteData['brand']['color_darker'] = $_POST['color_darker'] ?? '#0B1120';
        $siteData['brand']['color_surface'] = $_POST['color_surface'] ?? '#1F2937';
        $siteData['brand']['color_success'] = $_POST['color_success'] ?? '#10B981';
        $siteData['brand']['color_text'] = $_POST['color_text'] ?? '#F9FAFB';
        $siteData['brand']['color_muted'] = $_POST['color_muted'] ?? '#9CA3AF';

        $siteData['brand']['font_display'] = sanitize_input($_POST['font_display'] ?? 'Outfit');
        $siteData['brand']['font_body'] = sanitize_input($_POST['font_body'] ?? 'Inter');

        save_json('site.json', $siteData);
        audit_log('huisstijl_bewerkt', 'Huisstijl bijgewerkt');
        $_SESSION['flash_success'] = 'Huisstijl opgeslagen.';
    }
    header('Location: /beheer/?tab=huisstijl');
    exit;
}

$brand = $siteData['brand'] ?? [];
?>

<h1 class="text-2xl font-bold text-white mb-6">Huisstijl</h1>

<form method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <!-- Logo & Favicon -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Logo & Favicon</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Logo</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="logo" id="brand-logo" value="<?= e($brand['logo'] ?? '') ?>" class="admin-input flex-1" placeholder="/images/uploads/logo.png">
                    <button type="button" onclick="openMediaPicker('brand-logo')" class="btn-admin-sm">Kies</button>
                </div>
                <?php if (!empty($brand['logo'])): ?>
                <img src="<?= e($brand['logo']) ?>" class="mt-2 h-12 bg-white p-1 rounded" alt="Logo">
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Favicon</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="favicon" id="brand-favicon" value="<?= e($brand['favicon'] ?? '') ?>" class="admin-input flex-1" placeholder="/images/uploads/favicon.ico">
                    <button type="button" onclick="openMediaPicker('brand-favicon')" class="btn-admin-sm">Kies</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Colors -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Kleuren</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $colors = [
                'color_primary' => ['Primair', 'De hoofdkleur van knoppen, links en accenten op de website.'],
                'color_secondary' => ['Secundair', 'De kleur voor highlights, prijzen en call-to-action elementen.'],
                'color_dark' => ['Donker', 'De achtergrondkleur van de header en footer.'],
                'color_darker' => ['Donkerder', ''],
                'color_surface' => ['Oppervlak', ''],
                'color_success' => ['Succes', ''],
                'color_text' => ['Tekst', ''],
                'color_muted' => ['Gedempt', ''],
            ];
            foreach ($colors as $key => [$label, $helpText]):
                $value = $brand[$key] ?? '#000000';
            ?>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?= $label ?><?php if ($helpText): ?> <span class="help-tooltip" data-help="<?= e($helpText) ?>">?</span><?php endif; ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="<?= $key ?>" value="<?= e($value) ?>" class="w-10 h-10 rounded cursor-pointer border-0 bg-transparent">
                    <input type="text" value="<?= e($value) ?>" class="admin-input w-24 text-sm"
                           oninput="this.previousElementSibling.value=this.value"
                           onchange="this.previousElementSibling.value=this.value">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Preview -->
        <div class="mt-4 pt-4 border-t border-gray-700">
            <p class="text-sm text-gray-400 mb-2">Preview:</p>
            <div class="flex gap-2">
                <?php foreach ($colors as $key => [$clabel, $chelp]): ?>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-lg border border-gray-700" style="background-color: <?= e($brand[$key] ?? '#000') ?>"></div>
                    <span class="text-xs text-gray-500"><?= $clabel ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Fonts -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Lettertypen</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Display lettertype (titels) <span class="help-tooltip" data-help="Het lettertype voor koppen en titels.">?</span></label>
                <select name="font_display" class="admin-input w-full">
                    <?php
                    $fonts = ['Outfit', 'Inter', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Raleway', 'Playfair Display', 'Merriweather', 'Source Sans Pro', 'Nunito', 'Work Sans', 'DM Sans', 'Plus Jakarta Sans'];
                    foreach ($fonts as $font):
                    ?>
                    <option value="<?= $font ?>" <?= ($brand['font_display'] ?? 'Outfit') === $font ? 'selected' : '' ?>><?= $font ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Body lettertype (tekst) <span class="help-tooltip" data-help="Het lettertype voor lopende tekst en menu-items.">?</span></label>
                <select name="font_body" class="admin-input w-full">
                    <?php foreach ($fonts as $font): ?>
                    <option value="<?= $font ?>" <?= ($brand['font_body'] ?? 'Inter') === $font ? 'selected' : '' ?>><?= $font ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" name="save_brand" class="btn-admin btn-admin-primary">Opslaan</button>
    </div>
</form>
