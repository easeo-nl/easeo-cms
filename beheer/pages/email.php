<?php
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
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $testTo = sanitize_input($_POST['test_email'] ?? '');
        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Ongeldig e-mailadres.';
        } else {
            $companyName = site('company.name', 'EASEO CMS');
            $subject = 'Test e-mail — ' . $companyName;
            $body = '<h2>Test e-mail</h2>'
                . '<p>Dit is een test e-mail vanuit het EASEO CMS beheerpanel.</p>'
                . '<p>Als je dit leest, werkt de e-mailconfiguratie correct.</p>'
                . '<p><small>Verstuurd op ' . date('d-m-Y H:i:s') . '</small></p>';

            $result = send_mail($testTo, $subject, $body);
            if ($result === true) {
                $_SESSION['flash_success'] = 'Test e-mail verstuurd naar ' . $testTo;
                audit_log('test_email_verstuurd', "Naar: {$testTo}");
            } else {
                $_SESSION['flash_error'] = 'E-mail versturen mislukt: ' . $result;
            }
        }
    }
    header('Location: /beheer/?tab=email');
    exit;
}

// Handle save SMTP settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_smtp'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $newSmtp = [
            'enabled' => !empty($_POST['smtp_enabled']),
            'host' => sanitize_input($_POST['smtp_host'] ?? ''),
            'port' => sanitize_input($_POST['smtp_port'] ?? '465'),
            'encryption' => sanitize_input($_POST['smtp_encryption'] ?? 'ssl'),
            'username' => sanitize_input($_POST['smtp_username'] ?? ''),
            'password' => $smtp['password'] ?? '', // keep existing by default
            'from_email' => sanitize_input($_POST['smtp_from_email'] ?? ''),
            'from_name' => sanitize_input($_POST['smtp_from_name'] ?? ''),
        ];

        // Only update password if a new one was entered
        $newPassword = $_POST['smtp_password'] ?? '';
        if ($newPassword !== '') {
            $newSmtp['password'] = encrypt_smtp_password($newPassword);
        }

        $siteData['smtp'] = $newSmtp;
        file_put_contents(EASEO_DATA . '/site.json',
            json_encode($siteData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        audit_log('smtp_instellingen_opgeslagen', 'SMTP instellingen bijgewerkt');
        $_SESSION['flash_success'] = 'E-mailinstellingen opgeslagen.';
    }
    header('Location: /beheer/?tab=email');
    exit;
}

// Reload after save
$siteData = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
$smtp = $siteData['smtp'] ?? [];
$hasPassword = !empty($smtp['password']);
?>

<h1 class="text-2xl font-bold text-white mb-6">E-mail instellingen</h1>

<!-- SMTP Settings -->
<form method="POST" class="admin-card mb-6">
    <?= csrf_field() ?>

    <h3 class="text-md font-semibold text-white mb-4">SMTP-configuratie</h3>
    <p class="text-sm text-gray-400 mb-6">Configureer SMTP voor betrouwbare e-mailbezorging. Zonder SMTP worden e-mails verstuurd via PHP's standaard mail-functie.</p>

    <div class="mb-4">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="hidden" name="smtp_enabled" value="0">
            <input type="checkbox" name="smtp_enabled" value="1" <?= !empty($smtp['enabled']) ? 'checked' : '' ?> class="w-4 h-4 rounded">
            <span class="text-sm text-gray-300">SMTP inschakelen <span class="help-tooltip" data-help="Als dit uit staat, worden e-mails verstuurd via de standaard PHP mail-functie. Schakel SMTP in voor betrouwbare e-mailbezorging.">?</span></span>
        </label>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">SMTP server <span class="help-tooltip" data-help="Het adres van de SMTP-server. Voor Hostinger: smtp.hostinger.com. Voor Gmail: smtp.gmail.com.">?</span></label>
            <input type="text" name="smtp_host" value="<?= e($smtp['host'] ?? '') ?>" class="admin-input w-full" placeholder="smtp.hostinger.com">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Poort <span class="help-tooltip" data-help="465 voor SSL (standaard bij Hostinger), 587 voor TLS (Gmail en de meeste andere providers), 25 voor onbeveiligd (niet aanbevolen).">?</span></label>
            <select name="smtp_port" class="admin-input w-full">
                <option value="465" <?= ($smtp['port'] ?? '465') === '465' ? 'selected' : '' ?>>465 (SSL)</option>
                <option value="587" <?= ($smtp['port'] ?? '') === '587' ? 'selected' : '' ?>>587 (TLS)</option>
                <option value="25" <?= ($smtp['port'] ?? '') === '25' ? 'selected' : '' ?>>25 (onbeveiligd)</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Versleuteling <span class="help-tooltip" data-help="SSL voor poort 465, TLS voor poort 587. Kies 'geen' alleen als de provider dit vereist.">?</span></label>
            <select name="smtp_encryption" class="admin-input w-full">
                <option value="ssl" <?= ($smtp['encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                <option value="tls" <?= ($smtp['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                <option value="none" <?= ($smtp['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Geen</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Gebruikersnaam <span class="help-tooltip" data-help="Meestal het volledige e-mailadres. Bijvoorbeeld: noreply@uwdomein.nl">?</span></label>
            <input type="text" name="smtp_username" value="<?= e($smtp['username'] ?? '') ?>" class="admin-input w-full" placeholder="noreply@uwdomein.nl">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Wachtwoord <span class="help-tooltip" data-help="Het wachtwoord van het e-mailaccount. Wordt versleuteld opgeslagen.">?</span></label>
            <input type="password" name="smtp_password" value="" class="admin-input w-full" placeholder="<?= $hasPassword ? '••••••••' : '' ?>" autocomplete="new-password">
            <?php if ($hasPassword): ?>
            <p class="text-xs text-gray-500 mt-1">Wachtwoord is ingesteld. Laat leeg om het huidige wachtwoord te behouden.</p>
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Afzender e-mail <span class="help-tooltip" data-help="Het e-mailadres dat als afzender wordt getoond. Bijvoorbeeld: noreply@uwdomein.nl">?</span></label>
            <input type="email" name="smtp_from_email" value="<?= e($smtp['from_email'] ?? '') ?>" class="admin-input w-full" placeholder="noreply@uwdomein.nl">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Afzender naam <span class="help-tooltip" data-help="De naam die als afzender wordt getoond. Bijvoorbeeld: RWW Bouw of QP Marketing.">?</span></label>
            <input type="text" name="smtp_from_name" value="<?= e($smtp['from_name'] ?? '') ?>" class="admin-input w-full" placeholder="<?= e(site('company.name', 'Bedrijfsnaam')) ?>">
        </div>
    </div>

    <div class="mt-6">
        <button type="submit" name="save_smtp" class="btn-admin btn-admin-primary">Opslaan</button>
    </div>
</form>

<!-- Test Email -->
<form method="POST" class="admin-card mb-6">
    <?= csrf_field() ?>

    <h3 class="text-md font-semibold text-white mb-4">Test e-mail</h3>
    <p class="text-sm text-gray-400 mb-4">Verstuur een test e-mail om te controleren of de configuratie correct is.</p>

    <div class="flex items-end gap-3">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-300 mb-1">Ontvanger</label>
            <input type="email" name="test_email" value="<?= e(site('company.email')) ?>" required class="admin-input w-full" placeholder="test@voorbeeld.nl">
        </div>
        <button type="submit" name="send_test" class="btn-admin btn-admin-outline whitespace-nowrap">Verstuur test e-mail</button>
    </div>
</form>

<!-- Provider configs -->
<div class="admin-card">
    <h3 class="text-md font-semibold text-white mb-4">Veelgebruikte configuraties</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">Hostinger</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p>Server: <span class="text-gray-300">smtp.hostinger.com</span></p>
                <p>Poort: <span class="text-gray-300">465</span></p>
                <p>Versleuteling: <span class="text-gray-300">SSL</span></p>
                <p>Gebruikersnaam: <span class="text-gray-300">uw e-mailadres</span></p>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">Gmail</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p>Server: <span class="text-gray-300">smtp.gmail.com</span></p>
                <p>Poort: <span class="text-gray-300">587</span></p>
                <p>Versleuteling: <span class="text-gray-300">TLS</span></p>
                <p>Gebruikersnaam: <span class="text-gray-300">uw Gmail-adres</span></p>
                <p class="text-yellow-500 mt-1">Gebruik een App-wachtwoord, niet uw gewone wachtwoord</p>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-white mb-2">TransIP</h4>
            <div class="text-xs text-gray-400 space-y-1">
                <p>Server: <span class="text-gray-300">smtp.transip.email</span></p>
                <p>Poort: <span class="text-gray-300">465</span></p>
                <p>Versleuteling: <span class="text-gray-300">SSL</span></p>
            </div>
        </div>
    </div>
</div>
