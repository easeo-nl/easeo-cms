<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * Template: Pricing table section
 * Expects: $data array with titel, tekst, plannen (array of [naam, prijs, features, knop_tekst, knop_url, uitgelicht])
 */
$titel = ContentRepository::escape($data['titel'] ?? '');
$tekst = ContentRepository::escape($data['tekst'] ?? '');
$plannen = $data['plannen'] ?? [];
?>
<section class="py-16 bg-light">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <?php 
if ($titel) {
    ?>
        <div class="text-center mb-12">
            <h2 class="text-3xl font-display font-bold text-dark mb-4"><?php 
    echo $titel;
    ?></h2>
            <?php 
    if ($tekst) {
        ?>
            <p class="text-lg text-muted max-w-2xl mx-auto"><?php 
        echo $tekst;
        ?></p>
            <?php 
    }
    ?>
        </div>
        <?php 
}
?>

        <div class="grid grid-cols-1 md:grid-cols-<?php 
echo min(4, max(1, count($plannen)));
?> gap-6">
            <?php 
foreach ($plannen as $plan) {
    $uitgelicht = !empty($plan['uitgelicht']);
    ?>
            <div class="bg-white rounded-xl p-8 <?php 
    echo $uitgelicht ? 'ring-2 ring-primary shadow-xl' : 'shadow-sm';
    ?>">
                <?php 
    if ($uitgelicht) {
        ?>
                <span class="inline-block bg-primary text-white text-xs px-2 py-1 rounded-full mb-4"><?php 
        echo Translator::translate('pricing_popular_badge');
        ?></span>
                <?php 
    }
    ?>
                <h3 class="text-xl font-display font-bold text-dark"><?php 
    echo ContentRepository::escape($plan['naam'] ?? '');
    ?></h3>
                <div class="mt-4 mb-6">
                    <span class="text-4xl font-bold text-dark"><?php 
    echo ContentRepository::escape($plan['prijs'] ?? '');
    ?></span>
                    <?php 
    if (!empty($plan['periode'])) {
        ?>
                    <span class="text-muted">/<?php 
        echo ContentRepository::escape($plan['periode']);
        ?></span>
                    <?php 
    }
    ?>
                </div>
                <?php 
    if (!empty($plan['features'])) {
        ?>
                <ul class="space-y-3 mb-8">
                    <?php 
        foreach ($plan['features'] as $feature) {
            ?>
                    <li class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <?php 
            echo ContentRepository::escape($feature);
            ?>
                    </li>
                    <?php 
        }
        ?>
                </ul>
                <?php 
    }
    ?>
                <?php 
    if (!empty($plan['knop_tekst'])) {
        ?>
                <a href="<?php 
        echo ContentRepository::escape($plan['knop_url'] ?? '/contact');
        ?>" class="btn <?php 
        echo $uitgelicht ? 'btn-primary' : 'btn-outline';
        ?> w-full">
                    <?php 
        echo ContentRepository::escape($plan['knop_tekst']);
        ?>
                </a>
                <?php 
    }
    ?>
            </div>
            <?php 
}
?>
        </div>
    </div>
</section>
<?php 
