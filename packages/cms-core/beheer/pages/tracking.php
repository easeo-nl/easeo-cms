<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Tracking settings
 */
// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tracking'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $siteData = ContentRepository::loadJson('site.json');
        // Sanitize tracking IDs: only alphanumeric + hyphens + underscores
        $sanitizeId = fn($v) => preg_replace('/[^a-zA-Z0-9_-]/', '', trim($v ?? ''));
        $tracking_data = ['gtm_id' => $sanitizeId($_POST['gtm_id']), 'google_analytics_id' => $sanitizeId($_POST['google_analytics_id']), 'google_search_console' => trim($_POST['google_search_console'] ?? ''), 'google_ads_conversion_id' => $sanitizeId($_POST['google_ads_conversion_id']), 'google_ads_conversion_label' => $sanitizeId($_POST['google_ads_conversion_label']), 'facebook_pixel_id' => $sanitizeId($_POST['facebook_pixel_id']), 'custom_head_code' => '', 'custom_body_code' => ''];
        // Custom code fields: admin only
        if (is_admin()) {
            $tracking_data['custom_head_code'] = $_POST['custom_head_code'] ?? '';
            $tracking_data['custom_body_code'] = $_POST['custom_body_code'] ?? '';
        } else {
            // Preserve existing custom code for non-admins
            $tracking_data['custom_head_code'] = $siteData['tracking']['custom_head_code'] ?? '';
            $tracking_data['custom_body_code'] = $siteData['tracking']['custom_body_code'] ?? '';
        }
        $siteData['tracking'] = $tracking_data;
        ContentRepository::saveJson('site.json', $siteData);
        audit_log('tracking_bewerkt', 'Tracking instellingen bijgewerkt');
        $_SESSION['flash_success'] = Translator::translate('success_tracking_saved');
    }
    header('Location: /beheer/?tab=tracking');
    exit;
}
$tracking = ContentRepository::siteValue('tracking', []);
if (!is_array($tracking)) {
    $tracking = [];
}
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo Translator::translate('tracking_title');
?></h1>

<form method="POST" class="admin-card">
    <?php 
echo csrf_field();
?>

    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_gtm_heading');
?></h2>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_gtm_id_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_gtm_id');
?>">?</span></label>
            <input type="text" name="gtm_id" value="<?php 
echo ContentRepository::escape($tracking['gtm_id'] ?? '');
?>" class="admin-input w-full max-w-md" placeholder="GTM-XXXXXXX">
            <p class="text-xs text-gray-500 mt-1"><?php 
echo Translator::translate('tracking_gtm_hint');
?></p>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_ga4_heading');
?></h2>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_ga4_id_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_ga4_id');
?>">?</span></label>
            <input type="text" name="google_analytics_id" value="<?php 
echo ContentRepository::escape($tracking['google_analytics_id'] ?? '');
?>" class="admin-input w-full max-w-md" placeholder="G-XXXXXXXXXX">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_gsc_heading');
?></h2>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_gsc_code_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_gsc_code');
?>">?</span></label>
            <input type="text" name="google_search_console" value="<?php 
echo ContentRepository::escape($tracking['google_search_console'] ?? '');
?>" class="admin-input w-full max-w-md" placeholder="google-site-verification=...">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_gads_heading');
?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_gads_conversion_id_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_gads_conversion_id');
?>">?</span></label>
                    <input type="text" name="google_ads_conversion_id" value="<?php 
echo ContentRepository::escape($tracking['google_ads_conversion_id'] ?? '');
?>" class="admin-input w-full" placeholder="AW-XXXXXXXXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_gads_conversion_label_label');
?></label>
                    <input type="text" name="google_ads_conversion_label" value="<?php 
echo ContentRepository::escape($tracking['google_ads_conversion_label'] ?? '');
?>" class="admin-input w-full">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_fb_heading');
?></h2>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_fb_pixel_id_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_fb_pixel_id');
?>">?</span></label>
            <input type="text" name="facebook_pixel_id" value="<?php 
echo ContentRepository::escape($tracking['facebook_pixel_id'] ?? '');
?>" class="admin-input w-full max-w-md" placeholder="123456789012345">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('tracking_custom_code_heading');
?> <?php 
if (!is_admin()) {
    ?><span class="text-sm text-gray-500"><?php 
    echo Translator::translate('tracking_admin_only');
    ?></span><?php 
}
?></h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_custom_head_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_custom_head');
?>">?</span></label>
                <textarea name="custom_head_code" rows="4" class="admin-input w-full font-mono text-sm" <?php 
echo !is_admin() ? 'disabled' : '';
?>><?php 
echo ContentRepository::escape($tracking['custom_head_code'] ?? '');
?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('tracking_custom_body_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_custom_body');
?>">?</span></label>
                <textarea name="custom_body_code" rows="4" class="admin-input w-full font-mono text-sm" <?php 
echo !is_admin() ? 'disabled' : '';
?>><?php 
echo ContentRepository::escape($tracking['custom_body_code'] ?? '');
?></textarea>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-6 border-t border-gray-700 mt-6">
        <button type="submit" name="save_tracking" class="btn-admin btn-admin-primary"><?php 
echo Translator::translate('button_save');
?></button>
    </div>
</form>
<?php 
