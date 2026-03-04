<?php
/**
 * Template: Text block section
 * Expects: $data array with titel, tekst, achtergrond (light/white/dark)
 */
$titel = e($data['titel'] ?? '');
$tekst = $data['tekst'] ?? '';
$bg = ($data['achtergrond'] ?? 'white') === 'light' ? 'bg-light' : (($data['achtergrond'] ?? '') === 'dark' ? 'bg-dark text-white' : 'bg-white');
$centered = !empty($data['gecentreerd']);
?>
<section class="py-16 <?= $bg ?>">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 <?= $centered ? 'text-center' : '' ?>">
        <?php if ($titel): ?>
        <h2 class="text-3xl font-display font-bold mb-4"><?= $titel ?></h2>
        <?php endif; ?>
        <?php if ($tekst): ?>
        <div class="content-area text-lg leading-relaxed <?= $bg === 'bg-dark text-white' ? 'text-gray-300' : 'text-muted' ?>">
            <?= nl2br(e($tekst)) ?>
        </div>
        <?php endif; ?>
    </div>
</section>
