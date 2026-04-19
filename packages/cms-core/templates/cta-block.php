<?php
/**
 * Template: Call-to-action block
 * Expects: $data array with titel, tekst, knop_tekst, knop_url, achtergrond (primary/dark)
 */
$titel = e($data['titel'] ?? '');
$tekst = e($data['tekst'] ?? '');
$knopTekst = e($data['knop_tekst'] ?? '');
$knopUrl = e($data['knop_url'] ?? '/contact');
$bg = ($data['achtergrond'] ?? 'primary') === 'dark' ? 'bg-dark' : 'bg-primary';
?>
<section class="py-16 <?= $bg ?>">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <?php if ($titel): ?>
        <h2 class="text-3xl font-display font-bold text-white mb-4"><?= $titel ?></h2>
        <?php endif; ?>
        <?php if ($tekst): ?>
        <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto"><?= $tekst ?></p>
        <?php endif; ?>
        <?php if ($knopTekst): ?>
        <a href="<?= $knopUrl ?>" class="inline-flex items-center px-8 py-3 bg-white text-dark font-medium rounded-md hover:bg-gray-100 transition-colors">
            <?= $knopTekst ?>
        </a>
        <?php endif; ?>
    </div>
</section>
