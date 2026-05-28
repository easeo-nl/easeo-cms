<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — E-mail (SMTP) instellingen
 */
require_once EASEO_ROOT . '/includes/mailer.php';
$siteData = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
$smtp = $siteData['smtp'] ?? [];
$hasPassword = !empty($smtp['password']);
// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $testTo = sanitize_input($_POST['test_email'] ?? '');
        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = Translator::translate('error_invalid_email');
        } else {
            $companyName = ContentRepository::siteValue('company.name', 'EASEO CMS');
            $subject = 'Test e-mail — ' . $companyName;
            $body = '<h2>Test e-mail</h2>' . '<p>Dit is een test e-mail vanuit het EASEO CMS beheerpanel.</p>' . '<p>Als je dit leest, werkt de e-mailconfiguratie correct.</p>' . '<p><small>Verstuurd op ' . date('d-m-Y H:i:s') . '</small></p>';
            $result = send_mail($testTo, $subject, $body);
            if ($result === true) {
                $_SESSION['flash_success'] = Translator::translate('success_test_email_sent') . ' ' . $testTo;
                audit_log('test_email_verstuurd', "Naar: {$testTo}");
            } else {
                $_SESSION['flash_error'] = Translator::translate('error_email_send_failed') . ' ' . $result;
            }
        }
    }
    header('Location: /beheer/?tab=email');
    exit;
}
// Handle save SMTP settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $newSmtp = [
            'enabled' => !empty($_POST['smtp_enabled']),
            'host' => sanitize_input($_POST['smtp_host'] ?? ''),
            'port' => sanitize_input($_POST['smtp_port'] ?? '465'),
            'encryption' => sanitize_input($_POST['smtp_encryption'] ?? 'ssl'),
            'username' => sanitize_input($_POST['smtp_username'] ?? ''),
            'password' => $smtp['password'] ?? '',
            // keep existing by default
            'from_email' => sanitize_input($_POST['smtp_from_email'] ?? ''),
            'from_name' => sanitize_input($_POST['smtp_from_name'] ?? ''),
        ];
        // Only update password if a new one was entered
        $newPassword = $_POST['smtp_password'] ?? '';
        if ($newPassword !== '') {
            $newSmtp['password'] = encrypt_smtp_password($newPassword);
        }
        $siteData['smtp'] = $newSmtp;
        file_put_contents(EASEO_DATA . '/site.json', json_encode($siteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        audit_log('smtp_instellingen_opgeslagen', 'SMTP instellingen bijgewerkt');
        $_SESSION['flash_success'] = Translator::translate('success_email_settings_saved');
    }
    header('Location: /beheer/?tab=email');
    exit;
}
// Reload after save
$siteData = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
$smtp = $siteData['smtp'] ?? [];
$hasPassword = !empty($smtp['password']);
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo Translator::translate('email_settings_title');
?></h1>

<!-- SMTP Settings -->
<form method="POST" class="admin-card mb-6">
    <?php 
echo csrf_field();
?>

    <h3 class="text-md font-semibold text-white mb-4"><?php 
echo Translator::translate('smtp_config_heading');
?></h3>
    <p class="text-sm text-gray-400 mb-6"><?php 
echo Translator::translate('smtp_config_desc');
?></p>

    <div class="mb-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="smtp_enabled" value="0">
            <input type="checkbox" name="smtp_enabled" value="1" <?php 
echo !empty($smtp['enabled']) ? 'checked' : '';
?> class="w-4 h-4 rounded">
            <span class="text-sm text-gray-300"><?php 
echo Translator::translate('smtp_enable_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_enable');
?>">?</span></span>
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_server_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_server');
?>">?</span></label>
            <input type="text" name="smtp_host" value="<?php 
echo ContentRepository::escape($smtp['host'] ?? '');
?>" class="admin-input w-full" placeholder="smtp.hostinger.com">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_port_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_port');
?>">?</span></label>
            <select name="smtp_port" class="admin-input w-full">
                <option value="465" <?php 
echo ($smtp['port'] ?? '465') === '465' ? 'selected' : '';
?>>465 (SSL)</option>
                <option value="587" <?php 
echo ($smtp['port'] ?? '') === '587' ? 'selected' : '';
?>>587 (TLS)</option>
                <option value="25" <?php 
echo ($smtp['port'] ?? '') === '25' ? 'selected' : '';
?>><?php 
echo Translator::translate('smtp_port_25_label');
?></option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_encryption_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_encryption');
?>">?</span></label>
            <select name="smtp_encryption" class="admin-input w-full">
                <option value="ssl" <?php 
echo ($smtp['encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '';
?>>SSL</option>
                <option value="tls" <?php 
echo ($smtp['encryption'] ?? '') === 'tls' ? 'selected' : '';
?>>TLS</option>
                <option value="none" <?php 
echo ($smtp['encryption'] ?? '') === 'none' ? 'selected' : '';
?>><?php 
echo Translator::translate('smtp_encryption_none');
?></option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_username_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_username');
?>">?</span></label>
            <input type="text" name="smtp_username" value="<?php 
echo ContentRepository::escape($smtp['username'] ?? '');
?>" class="admin-input w-full" placeholder="noreply@uwdomein.nl">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_password_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_password');
?>">?</span></label>
            <input type="password" name="smtp_password" value="" class="admin-input w-full" placeholder="<?php 
echo $hasPassword ? '••••••••' : '';
?>" autocomplete="new-password">
            <?php 
if ($hasPassword) {
    ?>
            <p class="text-xs text-gray-500 mt-1"><?php 
    echo Translator::translate('hint_smtp_password_set');
    ?></p>
            <?php 
}
?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_from_email_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_from_email');
?>">?</span></label>
            <input type="email" name="smtp_from_email" value="<?php 
echo ContentRepository::escape($smtp['from_email'] ?? '');
?>" class="admin-input w-full" placeholder="noreply@uwdomein.nl">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_from_name_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_smtp_from_name');
?>">?</span></label>
            <input type="text" name="smtp_from_name" value="<?php 
echo ContentRepository::escape($smtp['from_name'] ?? '');
?>" class="admin-input w-full" placeholder="<?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'Bedrijfsnaam'));
?>">
        </div>
    </div>

    <div class="mt-6">
        <button type="submit" name="save_smtp" class="btn-admin btn-admin-primary"><?php 
echo Translator::translate('button_save');
?></button>
    </div>
</form>

<!-- Test Email -->
<form method="POST" class="admin-card mb-6">
    <?php 
echo csrf_field();
?>

    <h3 class="text-md font-semibold text-white mb-4"><?php 
echo Translator::translate('smtp_test_heading');
?></h3>
    <p class="text-sm text-gray-400 mb-4"><?php 
echo Translator::translate('smtp_test_desc');
?></p>

    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('smtp_test_recipient_label');
?></label>
            <input type="email" name="test_email" value="<?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.email'));
?>" required class="admin-input w-full" placeholder="test@voorbeeld.nl">
        </div>
        <button type="submit" name="send_test" class="btn-admin btn-admin-outline whitespace-nowrap"><?php 
echo Translator::translate('smtp_test_send_button');
?></button>
    </div>
</form>

<!-- Provider configs -->
<div class="admin-card">
    <h3 class="text-md font-semibold text-white mb-4"><?php 
echo Translator::translate('smtp_common_configs_heading');
?></h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">Hostinger</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p><?php 
echo Translator::translate('smtp_config_server_label');
?> <span class="text-gray-300">smtp.hostinger.com</span></p>
                <p><?php 
echo Translator::translate('smtp_config_port_label');
?> <span class="text-gray-300">465</span></p>
                <p><?php 
echo Translator::translate('smtp_config_encryption_label');
?> <span class="text-gray-300">SSL</span></p>
                <p><?php 
echo Translator::translate('smtp_config_username_label');
?> <span class="text-gray-300"><?php 
echo Translator::translate('smtp_hostinger_username_hint');
?></span></p>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">Gmail</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p><?php 
echo Translator::translate('smtp_config_server_label');
?> <span class="text-gray-300">smtp.gmail.com</span></p>
                <p><?php 
echo Translator::translate('smtp_config_port_label');
?> <span class="text-gray-300">587</span></p>
                <p><?php 
echo Translator::translate('smtp_config_encryption_label');
?> <span class="text-gray-300">TLS</span></p>
                <p><?php 
echo Translator::translate('smtp_config_username_label');
?> <span class="text-gray-300"><?php 
echo Translator::translate('smtp_gmail_username_hint');
?></span></p>
                <p class="text-yellow-500 mt-1"><?php 
echo Translator::translate('smtp_gmail_app_password_warning');
?></p>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">TransIP</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p><?php 
echo Translator::translate('smtp_config_server_label');
?> <span class="text-gray-300">smtp.transip.email</span></p>
                <p><?php 
echo Translator::translate('smtp_config_port_label');
?> <span class="text-gray-300">465</span></p>
                <p><?php 
echo Translator::translate('smtp_config_encryption_label');
?> <span class="text-gray-300">SSL</span></p>
            </div>
        </div>
    </div>
</div>
<?php 
