<?php
/**
 * Template: Image + text section
 * Expects: $data array with titel, tekst, afbeelding, positie (links/rechts), achtergrond
 */
$titel = e($data['titel'] ?? '');
$tekst = $data['tekst'] ?? '';
$afbeelding = $data['afbeelding'] ?? '';
$positie = ($data['positie'] ?? 'rechts') === 'links' ? 'md:order-first' : 'md:order-last';
$bg = ($data['achtergrond'] ?? 'white') === 'light' ? 'bg-light' : 'bg-white';
?>
<section class="py-16 <?= $bg ?>">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <?php if ($titel): ?>
                <h2 class="text-3xl font-display font-bold text-dark mb-4"><?= $titel ?></h2>
                <?php endif; ?>
                <?php if ($tekst): ?>
                <div class="content-area text-muted leading-relaxed">
                    <?= nl2br(e($tekst)) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($afbeelding): ?>
            <div class="<?= $positie ?>">
                <img src="<?= e($afbeelding) ?>" alt="<?= $titel ?>" class="rounded-lg shadow-md w-full">
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
