<?php
/**
 * Template: Hero section
 * Expects: $data array with titel, tekst, afbeelding, knop_tekst, knop_url
 */
$titel = e($data['titel'] ?? '');
$tekst = e($data['tekst'] ?? '');
$afbeelding = $data['afbeelding'] ?? '';
$knopTekst = e($data['knop_tekst'] ?? '');
$knopUrl = e($data['knop_url'] ?? '/contact');
?>
<section class="bg-gradient-to-br from-light to-white py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <?php if ($titel): ?>
                <h1 class="text-4xl md:text-5xl font-display font-bold text-dark mb-6"><?= $titel ?></h1>
                <?php endif; ?>
                <?php if ($tekst): ?>
                <p class="text-lg text-muted mb-8"><?= $tekst ?></p>
                <?php endif; ?>
                <?php if ($knopTekst): ?>
                <a href="<?= $knopUrl ?>" class="btn btn-primary text-base px-8 py-3"><?= $knopTekst ?></a>
                <?php endif; ?>
            </div>
            <?php if ($afbeelding): ?>
            <div>
                <img src="<?= e($afbeelding) ?>" alt="<?= $titel ?>" class="rounded-lg shadow-lg w-full">
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
