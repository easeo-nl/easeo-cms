<?php
/**
 * EASEO CMS — Form builder/editor
 */
require_once EASEO_ROOT . '/includes/form-engine.php';

$formId = $_GET['id'] ?? '';
$form = $formId ? get_form($formId) : null;

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_form'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = t('error_invalid_csrf');
    } else {
        $id = $_POST['form_id_key'] ?? '';
        $naam = trim($_POST['form_naam'] ?? '');
        $emailNaar = trim($_POST['email_naar'] ?? '');
        $bevestiging = trim($_POST['bevestiging'] ?? '');
        $knopTekst = trim($_POST['knop_tekst'] ?? 'Versturen');

        if (empty($naam)) {
            $_SESSION['flash_error'] = t('error_name_required');
        } else {
            if (!$id) {
                $id = preg_replace('/[^a-z0-9-]/', '', strtolower($naam));
                if (empty($id)) $id = 'form-' . substr(md5(uniqid()), 0, 6);
            }

            // Parse fields
            $velden = [];
            $fieldNames = $_POST['veld_naam'] ?? [];
            $fieldTypes = $_POST['veld_type'] ?? [];
            $fieldLabels = $_POST['veld_label'] ?? [];
            $fieldRequired = $_POST['veld_verplicht'] ?? [];

            for ($i = 0; $i < count($fieldNames); $i++) {
                $fn = trim($fieldNames[$i] ?? '');
                if (empty($fn)) continue;
                $velden[] = [
                    'naam' => preg_replace('/[^a-z0-9_]/', '', strtolower($fn)),
                    'type' => $fieldTypes[$i] ?? 'text',
                    'label' => trim($fieldLabels[$i] ?? ucfirst($fn)),
                    'verplicht' => !empty($fieldRequired[$i]),
                ];
            }

            $newForm = [
                'id' => $id,
                'naam' => $naam,
                'velden' => $velden,
                'email_naar' => $emailNaar,
                'bevestiging' => $bevestiging,
                'knop_tekst' => $knopTekst,
            ];

            // Update or add
            $forms = get_forms();
            $found = false;
            foreach ($forms as &$f) {
                if ($f['id'] === $id) {
                    $f = $newForm;
                    $found = true;
                    break;
                }
            }
            if (!$found) $forms[] = $newForm;

            save_forms($forms);
            audit_log('formulier_bewerkt', "Formulier: {$naam}");
            $_SESSION['flash_success'] = t('success_form_saved');
            header('Location: /beheer/?tab=formulier-edit&id=' . urlencode($id));
            exit;
        }
    }
}

$form = $formId ? get_form($formId) : null;
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?= $form ? t('form_edit_title') : t('form_new_title') ?></h1>
    <a href="/beheer/?tab=formulieren" class="btn-admin btn-admin-outline text-sm">&larr; <?= t('button_back') ?></a>
</div>

<form method="POST" class="space-y-6" id="form-builder">
    <?= csrf_field() ?>
    <?php if ($formId): ?>
    <input type="hidden" name="form_id_key" value="<?= e($formId) ?>">
    <?php endif; ?>

    <div class="admin-card">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_form_name') ?> <span class="help-tooltip" data-help="<?= t('tooltip_form_name') ?>">?</span></label>
                <input type="text" name="form_naam" value="<?= e($form['naam'] ?? '') ?>" required class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_email_notification_to') ?> <span class="help-tooltip" data-help="<?= t('tooltip_form_email_to') ?>">?</span></label>
                <input type="email" name="email_naar" value="<?= e($form['email_naar'] ?? '') ?>" class="admin-input w-full" placeholder="<?= t('placeholder_form_email_empty') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_confirmation_message') ?> <span class="help-tooltip" data-help="<?= t('tooltip_form_confirmation') ?>">?</span></label>
                <input type="text" name="bevestiging" value="<?= e($form['bevestiging'] ?? t('default_confirmation_message')) ?>" class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?= t('field_label_button_text') ?></label>
                <input type="text" name="knop_tekst" value="<?= e($form['knop_tekst'] ?? 'Versturen') ?>" class="admin-input w-full">
            </div>
        </div>
    </div>

    <div class="admin-card">
        <h3 class="text-lg font-semibold text-white mb-4"><?= t('section_fields') ?></h3>
        <div id="fields-container">
            <?php
            $fields = $form['velden'] ?? [];
            if (empty($fields)) $fields = [['naam' => '', 'type' => 'text', 'label' => '', 'verplicht' => false]];
            foreach ($fields as $i => $field):
            ?>
            <div class="field-row grid grid-cols-12 gap-2 mb-3 items-end">
                <div class="col-span-3">
                    <?php if ($i === 0): ?><label class="block text-xs text-gray-500 mb-1"><?= t('field_col_name_slug') ?></label><?php endif; ?>
                    <input type="text" name="veld_naam[]" value="<?= e($field['naam'] ?? '') ?>" class="admin-input w-full" placeholder="veld_naam">
                </div>
                <div class="col-span-3">
                    <?php if ($i === 0): ?><label class="block text-xs text-gray-500 mb-1"><?= t('field_col_label') ?></label><?php endif; ?>
                    <input type="text" name="veld_label[]" value="<?= e($field['label'] ?? '') ?>" class="admin-input w-full" placeholder="Label">
                </div>
                <div class="col-span-2">
                    <?php if ($i === 0): ?><label class="block text-xs text-gray-500 mb-1"><?= t('field_col_type') ?> <span class="help-tooltip" data-help="<?= t('tooltip_field_type') ?>">?</span></label><?php endif; ?>
                    <select name="veld_type[]" class="admin-input w-full">
                        <?php foreach (['text', 'email', 'tel', 'number', 'textarea', 'select', 'checkbox', 'date', 'url'] as $t_val): ?>
                        <option value="<?= $t_val ?>" <?= ($field['type'] ?? '') === $t_val ? 'selected' : '' ?>><?= $t_val ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <?php if ($i === 0): ?><label class="block text-xs text-gray-500 mb-1"><?= t('field_col_required') ?> <span class="help-tooltip" data-help="<?= t('tooltip_field_required') ?>">?</span></label><?php endif; ?>
                    <select name="veld_verplicht[]" class="admin-input w-full">
                        <option value="1" <?= !empty($field['verplicht']) ? 'selected' : '' ?>><?= t('option_yes') ?></option>
                        <option value="" <?= empty($field['verplicht']) ? 'selected' : '' ?>><?= t('option_no') ?></option>
                    </select>
                </div>
                <div class="col-span-2 flex gap-1">
                    <button type="button" onclick="removeField(this)" class="btn-admin-sm bg-red-600 hover:bg-red-700 text-xs">&times;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="button" onclick="addField()" class="btn-admin btn-admin-outline text-sm mt-2"><?= t('button_add_field_form') ?></button>
    </div>

    <div class="flex justify-end">
        <button type="submit" name="save_form" class="btn-admin btn-admin-primary"><?= t('button_save') ?></button>
    </div>
</form>

<script>
function addField() {
    var container = document.getElementById('fields-container');
    var row = document.createElement('div');
    row.className = 'field-row grid grid-cols-12 gap-2 mb-3 items-end';
    row.innerHTML = '<div class="col-span-3"><input type="text" name="veld_naam[]" class="admin-input w-full" placeholder="veld_naam"></div>' +
        '<div class="col-span-3"><input type="text" name="veld_label[]" class="admin-input w-full" placeholder="Label"></div>' +
        '<div class="col-span-2"><select name="veld_type[]" class="admin-input w-full"><option value="text">text</option><option value="email">email</option><option value="tel">tel</option><option value="number">number</option><option value="textarea">textarea</option><option value="select">select</option><option value="checkbox">checkbox</option><option value="date">date</option><option value="url">url</option></select></div>' +
        '<div class="col-span-2"><select name="veld_verplicht[]" class="admin-input w-full"><option value="1"><?= t('option_yes') ?></option><option value=""><?= t('option_no') ?></option></select></div>' +
        '<div class="col-span-2 flex gap-1"><button type="button" onclick="removeField(this)" class="btn-admin-sm bg-red-600 hover:bg-red-700 text-xs">&times;</button></div>';
    container.appendChild(row);
}

function removeField(btn) {
    var rows = document.querySelectorAll('.field-row');
    if (rows.length > 1) {
        btn.closest('.field-row').remove();
    }
}
</script>
