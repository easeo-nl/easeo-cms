<?php
/**
 * Template: Features grid section
 * Expects: $data array with titel, tekst, items (array of [titel, tekst, icoon])
 */
$titel = e($data['titel'] ?? '');
$tekst = e($data['tekst'] ?? '');
$items = $data['items'] ?? [];
$cols = min(4, max(2, count($items)));
?>
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <?php if ($titel): ?>
        <div class="text-center mb-12">
            <h2 class="text-3xl font-display font-bold text-dark mb-4"><?= $titel ?></h2>
            <?php if ($tekst): ?>
            <p class="text-lg text-muted max-w-2xl mx-auto"><?= $tekst ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-<?= $cols ?> gap-8">
            <?php foreach ($items as $item): ?>
            <div class="text-center p-6">
                <?php if (!empty($item['icoon'])): ?>
                <div class="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <span class="text-2xl"><?= $item['icoon'] ?></span>
                </div>
                <?php endif; ?>
                <h3 class="text-lg font-display font-semibold text-dark mb-2"><?= e($item['titel'] ?? '') ?></h3>
                <p class="text-muted"><?= e($item['tekst'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
