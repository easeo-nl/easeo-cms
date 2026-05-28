<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * Template: Image + text section
 * Expects: $data array with titel, tekst, afbeelding, positie (links/rechts), achtergrond
 */
$titel = ContentRepository::escape($data['titel'] ?? '');
$tekst = $data['tekst'] ?? '';
$afbeelding = $data['afbeelding'] ?? '';
$positie = ($data['positie'] ?? 'rechts') === 'links' ? 'md:order-first' : 'md:order-last';
$bg = ($data['achtergrond'] ?? 'white') === 'light' ? 'bg-light' : 'bg-white';
?>
<section class="py-16 <?php 
echo $bg;
?>">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <?php 
if ($titel) {
    ?>
                <h2 class="text-3xl font-display font-bold text-dark mb-4"><?php 
    echo $titel;
    ?></h2>
                <?php 
}
?>
                <?php 
if ($tekst) {
    ?>
                <div class="content-area text-muted leading-relaxed">
                    <?php 
    echo nl2br(ContentRepository::escape($tekst));
    ?>
                </div>
                <?php 
}
?>
            </div>
            <?php 
if ($afbeelding) {
    ?>
            <div class="<?php 
    echo $positie;
    ?>">
                <img src="<?php 
    echo ContentRepository::escape($afbeelding);
    ?>" alt="<?php 
    echo $titel;
    ?>" class="rounded-lg shadow-md w-full">
            </div>
            <?php 
}
?>
        </div>
    </div>
</section>
<?php 
