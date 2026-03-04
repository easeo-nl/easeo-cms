<?php
/**
 * EASEO CMS — Tracking settings
 */

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tracking'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $siteData = load_json('site.json');
        $siteData['tracking'] = [
            'gtm_id' => trim($_POST['gtm_id'] ?? ''),
            'google_analytics_id' => trim($_POST['google_analytics_id'] ?? ''),
            'google_search_console' => trim($_POST['google_search_console'] ?? ''),
            'google_ads_conversion_id' => trim($_POST['google_ads_conversion_id'] ?? ''),
            'google_ads_conversion_label' => trim($_POST['google_ads_conversion_label'] ?? ''),
            'facebook_pixel_id' => trim($_POST['facebook_pixel_id'] ?? ''),
            'custom_head_code' => $_POST['custom_head_code'] ?? '',
            'custom_body_code' => $_POST['custom_body_code'] ?? '',
        ];
        save_json('site.json', $siteData);
        audit_log('tracking_bewerkt', 'Tracking instellingen bijgewerkt');
        $_SESSION['flash_success'] = 'Tracking instellingen opgeslagen.';
    }
    header('Location: /beheer/?tab=tracking');
    exit;
}

$tracking = site('tracking', []);
if (!is_array($tracking)) $tracking = [];
?>

<h1 class="text-2xl font-bold text-white mb-6">Tracking & Analytics</h1>

<form method="POST" class="admin-card">
    <?= csrf_field() ?>

    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Google Tag Manager</h2>
            <label class="block text-sm font-medium text-gray-300 mb-1">GTM Container ID <span class="help-tooltip" data-help="Google Tag Manager container-ID. Begint met GTM-. Hiermee kun je alle tracking centraal beheren.">?</span></label>
            <input type="text" name="gtm_id" value="<?= e($tracking['gtm_id'] ?? '') ?>" class="admin-input w-full max-w-md" placeholder="GTM-XXXXXXX">
            <p class="text-xs text-gray-500 mt-1">Wordt alleen geladen na cookie consent.</p>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Google Analytics 4</h2>
            <label class="block text-sm font-medium text-gray-300 mb-1">GA4 Measurement ID <span class="help-tooltip" data-help="Google Analytics 4 tracking-ID. Begint met G-. Voor websitestatistieken.">?</span></label>
            <input type="text" name="google_analytics_id" value="<?= e($tracking['google_analytics_id'] ?? '') ?>" class="admin-input w-full max-w-md" placeholder="G-XXXXXXXXXX">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Google Search Console</h2>
            <label class="block text-sm font-medium text-gray-300 mb-1">Verificatie code <span class="help-tooltip" data-help="De verificatiecode van Google Search Console. Alleen de code, niet de hele meta-tag.">?</span></label>
            <input type="text" name="google_search_console" value="<?= e($tracking['google_search_console'] ?? '') ?>" class="admin-input w-full max-w-md" placeholder="google-site-verification=...">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Google Ads</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Conversion ID <span class="help-tooltip" data-help="Voor het meten van conversies uit Google Ads campagnes.">?</span></label>
                    <input type="text" name="google_ads_conversion_id" value="<?= e($tracking['google_ads_conversion_id'] ?? '') ?>" class="admin-input w-full" placeholder="AW-XXXXXXXXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Conversion Label</label>
                    <input type="text" name="google_ads_conversion_label" value="<?= e($tracking['google_ads_conversion_label'] ?? '') ?>" class="admin-input w-full">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Facebook Pixel</h2>
            <label class="block text-sm font-medium text-gray-300 mb-1">Pixel ID <span class="help-tooltip" data-help="Voor het meten van websitebezoek vanuit Facebook en Instagram advertenties.">?</span></label>
            <input type="text" name="facebook_pixel_id" value="<?= e($tracking['facebook_pixel_id'] ?? '') ?>" class="admin-input w-full max-w-md" placeholder="123456789012345">
        </div>

        <div>
            <h2 class="text-lg font-semibold text-white mb-4">Aangepaste code</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Custom code in &lt;head&gt; <span class="help-tooltip" data-help="HTML of JavaScript die in de <head> van elke pagina wordt geplaatst. Alleen voor gevorderd gebruik.">?</span></label>
                <textarea name="custom_head_code" rows="4" class="admin-input w-full font-mono text-sm"><?= e($tracking['custom_head_code'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Custom code in &lt;body&gt; <span class="help-tooltip" data-help="HTML of JavaScript die vlak voor </body> wordt geplaatst. Alleen voor gevorderd gebruik.">?</span></label>
                <textarea name="custom_body_code" rows="4" class="admin-input w-full font-mono text-sm"><?= e($tracking['custom_body_code'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="flex justify-end pt-6 border-t border-gray-700 mt-6">
        <button type="submit" name="save_tracking" class="btn-admin btn-admin-primary">Opslaan</button>
    </div>
</form>
