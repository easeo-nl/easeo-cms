<?php
/**
 * EASEO CMS — Form list in admin
 */
require_once EASEO_ROOT . '/includes/form-engine.php';

$forms = get_forms();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $fid = $_POST['form_id'] ?? '';
        $newForms = [];
        $deleted = false;
        foreach ($forms as $form) {
            if (($form['id'] ?? '') === $fid) {
                $deleted = true;
                audit_log('formulier_verwijderd', "Formulier: " . ($form['naam'] ?? $fid));
                continue;
            }
            $newForms[] = $form;
        }
        if ($deleted) {
            save_forms($newForms);
            $_SESSION['flash_success'] = t('success_form_deleted');
        }
    }
    header('Location: /beheer/?tab=formulieren');
    exit;
}

$forms = get_forms();
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?= t('forms_list_title') ?></h1>
    <a href="/beheer/?tab=formulier-edit" class="btn-admin btn-admin-primary"><?= t('button_new_form') ?></a>
</div>

<div class="admin-card">
    <?php if (empty($forms)): ?>
        <p class="text-gray-500"><?= t('forms_no_forms') ?></p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?= t('table_header_name') ?></th>
                <th><?= t('table_header_id') ?></th>
                <th><?= t('table_header_fields') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forms as $form): $id = $form['id'] ?? ''; ?>
            <tr>
                <td class="text-white">
                    <a href="/beheer/?tab=formulier-edit&id=<?= e($id) ?>" class="hover:text-blue-400"><?= e($form['naam'] ?? $id) ?></a>
                </td>
                <td class="text-gray-500 font-mono text-sm"><?= e($id) ?></td>
                <td class="text-gray-400"><?= count($form['velden'] ?? []) ?> <?= t('unit_fields') ?></td>
                <td class="text-right">
                    <a href="/beheer/?tab=formulier-edit&id=<?= e($id) ?>" class="text-blue-400 hover:text-blue-300 text-sm mr-2"><?= t('action_edit') ?></a>
                    <form method="POST" class="inline" onsubmit="return confirm('<?= t('confirm_delete') ?>')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="form_id" value="<?= e($id) ?>">
                        <button type="submit" name="delete_form" class="text-red-400 hover:text-red-300 text-sm"><?= t('action_delete') ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
