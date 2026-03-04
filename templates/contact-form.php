<?php
/**
 * Template: Contact form section
 * Expects: $data array with titel, tekst, formulier_id
 */
require_once EASEO_ROOT . '/includes/form-engine.php';

$titel = e($data['titel'] ?? 'Neem contact op');
$tekst = e($data['tekst'] ?? '');
$formId = $data['formulier_id'] ?? 'contact';
?>
<section class="py-16 bg-light">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <?php if ($titel): ?>
        <div class="text-center mb-8">
            <h2 class="text-3xl font-display font-bold text-dark mb-4"><?= $titel ?></h2>
            <?php if ($tekst): ?>
            <p class="text-muted"><?= $tekst ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <?= render_form($formId) ?>
        </div>
    </div>
</section>
