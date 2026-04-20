<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * Template: Hero section
 * Expects: $data array with titel, tekst, afbeelding, knop_tekst, knop_url
 */
$titel = ContentRepository::escape($data['titel'] ?? '');
$tekst = ContentRepository::escape($data['tekst'] ?? '');
$afbeelding = $data['afbeelding'] ?? '';
$knopTekst = ContentRepository::escape($data['knop_tekst'] ?? '');
$knopUrl = ContentRepository::escape($data['knop_url'] ?? '/contact');
?>
<section class="bg-gradient-to-br from-light to-white py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <?php 
if ($titel) {
    ?>
                <h1 class="text-4xl md:text-5xl font-display font-bold text-dark mb-6"><?php 
    echo $titel;
    ?></h1>
                <?php 
}
?>
                <?php 
if ($tekst) {
    ?>
                <p class="text-lg text-muted mb-8"><?php 
    echo $tekst;
    ?></p>
                <?php 
}
?>
                <?php 
if ($knopTekst) {
    ?>
                <a href="<?php 
    echo $knopUrl;
    ?>" class="btn btn-primary text-base px-8 py-3"><?php 
    echo $knopTekst;
    ?></a>
                <?php 
}
?>
            </div>
            <?php 
if ($afbeelding) {
    ?>
            <div>
                <img src="<?php 
    echo ContentRepository::escape($afbeelding);
    ?>" alt="<?php 
    echo $titel;
    ?>" class="rounded-lg shadow-lg w-full">
            </div>
            <?php 
}
?>
        </div>
    </div>
</section>
<?php 
