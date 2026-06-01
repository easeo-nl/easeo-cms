<?php
use Easeo\Cms\Config\SecretStatus;
use Easeo\Cms\Lang\Translator;

/**
 * EASEO CMS — Geheimen (Secrets) status-pagina
 * Admin-only read-only overview of environment-backed secrets.
 * Values are never shown — only configured/not-configured status.
 */

// Admin-only — also enforced by $adminTabs in beheer/index.php
require_admin();

$secrets         = SecretStatus::summary();
$missingRequired = SecretStatus::missingRequired();
?>

<div class="admin-card mb-6">
    <h1 class="text-2xl font-semibold mb-2"><?php echo Translator::translate('admin_secrets_title'); ?></h1>
    <p class="text-gray-400 mb-4"><?php echo Translator::translate('admin_secrets_intro'); ?></p>

    <?php if (!empty($missingRequired)): ?>
        <div class="admin-alert admin-alert-warning mb-4">
            <?php echo Translator::translate('admin_secrets_missing_required'); ?>
            <ul class="list-disc list-inside mt-2">
                <?php foreach ($missingRequired as $key): ?>
                    <li><code><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <table class="admin-table w-full">
        <thead>
            <tr>
                <th class="text-left"><?php echo Translator::translate('admin_secrets_col_key'); ?></th>
                <th class="text-left"><?php echo Translator::translate('admin_secrets_col_label'); ?></th>
                <th class="text-left"><?php echo Translator::translate('admin_secrets_col_status'); ?></th>
                <th class="text-left"><?php echo Translator::translate('admin_secrets_col_hint'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($secrets as $secret): ?>
                <tr>
                    <td><code class="text-sm"><?php echo htmlspecialchars($secret['key'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                    <td><?php echo htmlspecialchars($secret['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if ($secret['configured']): ?>
                            <span class="badge badge-success"><?php echo Translator::translate('admin_secrets_configured'); ?></span>
                        <?php elseif ($secret['required']): ?>
                            <span class="badge badge-error"><?php echo Translator::translate('admin_secrets_missing'); ?></span>
                        <?php else: ?>
                            <span class="badge badge-muted"><?php echo Translator::translate('admin_secrets_not_set'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-gray-400"><?php echo htmlspecialchars($secret['hint'], ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="admin-card mt-6">
    <h2 class="text-lg font-semibold mb-2"><?php echo Translator::translate('admin_secrets_how_to_set'); ?></h2>
    <p class="text-sm text-gray-400 mb-2"><?php echo Translator::translate('admin_secrets_env_explanation'); ?></p>
    <pre class="bg-gray-900 p-3 rounded text-xs overflow-x-auto">SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=noreply@<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'example.com', ENT_QUOTES, 'UTF-8'); ?>

SMTP_PASSWORD=your-password-here
SMTP_ENCRYPTION=tls
GTM_ID=GTM-XXXXXXX</pre>
</div>
