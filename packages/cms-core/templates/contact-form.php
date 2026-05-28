<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * Template: Contact form section
 * Expects: $data array with titel, tekst, formulier_id
 */
require_once EASEO_ROOT . '/includes/form-engine.php';
$titel = ContentRepository::escape($data['titel'] ?? t('contact_form_template_title'));
$tekst = ContentRepository::escape($data['tekst'] ?? '');
$formId = $data['formulier_id'] ?? 'contact';
?>
<section class="py-16 bg-light">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <?php 
if ($titel) {
    ?>
        <div class="text-center mb-8">
            <h2 class="text-3xl font-display font-bold text-dark mb-4"><?php 
    echo $titel;
    ?></h2>
            <?php 
    if ($tekst) {
        ?>
            <p class="text-muted"><?php 
        echo $tekst;
        ?></p>
            <?php 
    }
    ?>
        </div>
        <?php 
}
?>

        <div class="bg-white rounded-xl shadow-sm p-8">
            <?php 
echo render_form($formId);
?>
        </div>
    </div>
</section>
<?php 
