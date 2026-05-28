<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Site footer
 */
?>
    </main>

    <footer class="bg-dark text-gray-300 mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'EASEO'));
?></h3>
                    <?php 
if (ContentRepository::siteValue('company.address') || ContentRepository::siteValue('company.postcode') || ContentRepository::siteValue('company.city')) {
    ?>
                    <p class="text-sm text-gray-400">
                        <?php 
    if (ContentRepository::siteValue('company.address')) {
        echo ContentRepository::escape(ContentRepository::siteValue('company.address'));
        ?><br><?php 
    }
    ?>
                        <?php 
    if (ContentRepository::siteValue('company.postcode') || ContentRepository::siteValue('company.city')) {
        echo ContentRepository::escape(ContentRepository::siteValue('company.postcode'));
        ?> <?php 
        echo ContentRepository::escape(ContentRepository::siteValue('company.city'));
    }
    ?>
                    </p>
                    <?php 
}
?>
                </div>

                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?php 
echo t('footer_contact_heading');
?></h3>
                    <div class="space-y-1 text-sm text-gray-400">
                        <?php 
if (ContentRepository::siteValue('company.email')) {
    ?>
                        <p><a href="mailto:<?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.email'));
    ?>" class="hover:text-white transition-colors"><?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.email'));
    ?></a></p>
                        <?php 
}
?>
                        <?php 
if (ContentRepository::siteValue('company.phone')) {
    ?>
                        <p><a href="tel:<?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.phone'));
    ?>" class="hover:text-white transition-colors"><?php 
    echo ContentRepository::escape(ContentRepository::siteValue('company.phone'));
    ?></a></p>
                        <?php 
}
?>
                    </div>
                </div>

                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?php 
echo t('footer_links_heading');
?></h3>
                    <div class="flex flex-col space-y-1">
                        <?php 
echo render_footer_nav();
?>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500">&copy; <?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.copyright_year', date('Y')));
?> <?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', 'EASEO'));
?>. <?php 
echo t('footer_all_rights_reserved');
?></p>

                <?php 
$socials = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'twitter' => 'X', 'youtube' => 'YouTube'];
$hasSocials = false;
foreach ($socials as $key => $name) {
    if (ContentRepository::siteValue("social.{$key}")) {
        $hasSocials = true;
        break;
    }
}
?>
                <?php 
if ($hasSocials) {
    ?>
                <div class="flex space-x-4">
                    <?php 
    foreach ($socials as $key => $name) {
        ?>
                        <?php 
        if (ContentRepository::siteValue("social.{$key}")) {
            ?>
                        <a href="<?php 
            echo ContentRepository::escape(ContentRepository::siteValue("social.{$key}"));
            ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-white text-sm transition-colors"><?php 
            echo $name;
            ?></a>
                        <?php 
        }
        ?>
                    <?php 
    }
    ?>
                </div>
                <?php 
}
?>
            </div>

            <div class="text-center mt-4">
                <p class="text-xs" style="color: var(--color-muted); opacity: 0.5;">
                    <a href="https://easeo.nl" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;"><?php 
echo t('footer_powered_by');
?></a>
                </p>
            </div>
        </div>
    </footer>

    <?php 
include __DIR__ . '/cookie-consent.php';
?>

    <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
        var menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });

    // Telefoon-klik tracking — delegated, zodat dynamisch toegevoegde tel: links ook getracked worden.
    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('a[href^="tel:"]');
        if (!link) return;
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            'event': 'telefoon_klik',
            'phone_number': link.getAttribute('href').replace(/^tel:/, '')
        });
    });
    </script>
</body>
</html>
<?php 
