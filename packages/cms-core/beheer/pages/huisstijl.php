<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Audit\AuditLogger;
/**
 * EASEO CMS — Brand editor (colors, fonts, logo)
 */
$siteData = ContentRepository::loadJson('site.json');
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brand'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
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
        ContentRepository::saveJson('site.json', $siteData);
        AuditLogger::log('huisstijl_bewerkt', 'Huisstijl bijgewerkt');
        $_SESSION['flash_success'] = Translator::translate('success_branding_saved');
    }
    header('Location: /beheer/?tab=huisstijl');
    exit;
}
$brand = $siteData['brand'] ?? [];
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo Translator::translate('branding_title');
?></h1>

<form method="POST" class="space-y-6">
    <?php 
echo csrf_field();
?>

    <!-- Logo & Favicon -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('branding_logo_section');
?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_logo');
?></label>
                <div class="flex items-center gap-2">
                    <input type="text" name="logo" id="brand-logo" value="<?php 
echo ContentRepository::escape($brand['logo'] ?? '');
?>" class="admin-input flex-1" placeholder="/images/uploads/logo.png">
                    <button type="button" onclick="openMediaPicker('brand-logo')" class="btn-admin-sm"><?php 
echo Translator::translate('button_choose_media');
?></button>
                </div>
                <?php 
if (!empty($brand['logo'])) {
    ?>
                <img src="<?php 
    echo ContentRepository::escape($brand['logo']);
    ?>" class="mt-2 h-12 bg-white p-1 rounded" alt="Logo">
                <?php 
}
?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_favicon');
?></label>
                <div class="flex items-center gap-2">
                    <input type="text" name="favicon" id="brand-favicon" value="<?php 
echo ContentRepository::escape($brand['favicon'] ?? '');
?>" class="admin-input flex-1" placeholder="/images/uploads/favicon.ico">
                    <button type="button" onclick="openMediaPicker('brand-favicon')" class="btn-admin-sm"><?php 
echo Translator::translate('button_choose_media');
?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Colors -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('branding_colors_section');
?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php 
$colors = ['color_primary' => ['color_primary_label', 'tooltip_color_primary'], 'color_secondary' => ['color_secondary_label', 'tooltip_color_secondary'], 'color_dark' => ['color_dark_label', 'tooltip_color_dark'], 'color_darker' => ['color_darker_label', ''], 'color_surface' => ['color_surface_label', ''], 'color_success' => ['color_success_label', ''], 'color_text' => ['color_text_label', ''], 'color_muted' => ['color_muted_label', '']];
foreach ($colors as $key => [$labelKey, $tooltipKey]) {
    $value = $brand[$key] ?? '#000000';
    ?>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
    echo Translator::translate($labelKey);
    if ($tooltipKey) {
        ?> <span class="help-tooltip" data-help="<?php 
        echo Translator::translate($tooltipKey);
        ?>">?</span><?php 
    }
    ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="<?php 
    echo $key;
    ?>" value="<?php 
    echo ContentRepository::escape($value);
    ?>" class="w-10 h-10 rounded cursor-pointer border-0 bg-transparent">
                    <input type="text" value="<?php 
    echo ContentRepository::escape($value);
    ?>" class="admin-input w-24 text-sm"
                           oninput="this.previousElementSibling.value=this.value"
                           onchange="this.previousElementSibling.value=this.value">
                </div>
            </div>
            <?php 
}
?>
        </div>

        <!-- Preview -->
        <div class="mt-4 pt-4 border-t border-gray-700">
            <p class="text-sm text-gray-400 mb-2"><?php 
echo Translator::translate('color_preview_label');
?></p>
            <div class="flex gap-2">
                <?php 
foreach ($colors as $key => [$labelKey, $tooltipKey]) {
    ?>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-lg border border-gray-700" style="background-color: <?php 
    echo ContentRepository::escape($brand[$key] ?? '#000');
    ?>"></div>
                    <span class="text-xs text-gray-500"><?php 
    echo Translator::translate($labelKey);
    ?></span>
                </div>
                <?php 
}
?>
            </div>
        </div>
    </div>

    <!-- Fonts -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('branding_fonts_section');
?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('font_display_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_font_display');
?>">?</span></label>
                <select name="font_display" class="admin-input w-full">
                    <?php 
$fonts = ['Outfit', 'Inter', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Raleway', 'Playfair Display', 'Merriweather', 'Source Sans Pro', 'Nunito', 'Work Sans', 'DM Sans', 'Plus Jakarta Sans'];
foreach ($fonts as $font) {
    ?>
                    <option value="<?php 
    echo $font;
    ?>" <?php 
    echo ($brand['font_display'] ?? 'Outfit') === $font ? 'selected' : '';
    ?>><?php 
    echo $font;
    ?></option>
                    <?php 
}
?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('font_body_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_font_body');
?>">?</span></label>
                <select name="font_body" class="admin-input w-full">
                    <?php 
foreach ($fonts as $font) {
    ?>
                    <option value="<?php 
    echo $font;
    ?>" <?php 
    echo ($brand['font_body'] ?? 'Inter') === $font ? 'selected' : '';
    ?>><?php 
    echo $font;
    ?></option>
                    <?php 
}
?>
                </select>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" name="save_brand" class="btn-admin btn-admin-primary"><?php 
echo Translator::translate('button_save');
?></button>
    </div>
</form>
<?php 
