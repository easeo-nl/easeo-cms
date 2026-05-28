<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * Template: Call-to-action block
 * Expects: $data array with titel, tekst, knop_tekst, knop_url, achtergrond (primary/dark)
 */
$titel = ContentRepository::escape($data['titel'] ?? '');
$tekst = ContentRepository::escape($data['tekst'] ?? '');
$knopTekst = ContentRepository::escape($data['knop_tekst'] ?? '');
$knopUrl = ContentRepository::escape($data['knop_url'] ?? '/contact');
$bg = ($data['achtergrond'] ?? 'primary') === 'dark' ? 'bg-dark' : 'bg-primary';
?>
<section class="py-16 <?php 
echo $bg;
?>">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <?php 
if ($titel) {
    ?>
        <h2 class="text-3xl font-display font-bold text-white mb-4"><?php 
    echo $titel;
    ?></h2>
        <?php 
}
?>
        <?php 
if ($tekst) {
    ?>
        <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto"><?php 
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
    ?>" class="inline-flex items-center px-8 py-3 bg-white text-dark font-medium rounded-md hover:bg-gray-100 transition-colors">
            <?php 
    echo $knopTekst;
    ?>
        </a>
        <?php 
}
?>
    </div>
</section>
<?php 
