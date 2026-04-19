<?php
/**
 * EASEO CMS — Site footer
 */
?>
    </main>

    <footer class="bg-dark text-gray-300 mt-auto">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?= e(site('company.name', 'EASEO')) ?></h3>
                    <?php if (site('company.address') || site('company.postcode') || site('company.city')): ?>
                    <p class="text-sm text-gray-400">
                        <?php if (site('company.address')): ?><?= e(site('company.address')) ?><br><?php endif; ?>
                        <?php if (site('company.postcode') || site('company.city')): ?><?= e(site('company.postcode')) ?> <?= e(site('company.city')) ?><?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?= t('footer_contact_heading') ?></h3>
                    <div class="space-y-1 text-sm text-gray-400">
                        <?php if (site('company.email')): ?>
                        <p><a href="mailto:<?= e(site('company.email')) ?>" class="hover:text-white transition-colors"><?= e(site('company.email')) ?></a></p>
                        <?php endif; ?>
                        <?php if (site('company.phone')): ?>
                        <p><a href="tel:<?= e(site('company.phone')) ?>" class="hover:text-white transition-colors"><?= e(site('company.phone')) ?></a></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <h3 class="text-white font-display font-bold text-lg mb-3"><?= t('footer_links_heading') ?></h3>
                    <div class="flex flex-col space-y-1">
                        <?= render_footer_nav() ?>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-8 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500">&copy; <?= e(site('company.copyright_year', date('Y'))) ?> <?= e(site('company.name', 'EASEO')) ?>. <?= t('footer_all_rights_reserved') ?></p>

                <?php
                $socials = [
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'linkedin' => 'LinkedIn',
                    'twitter' => 'X',
                    'youtube' => 'YouTube',
                ];
                $hasSocials = false;
                foreach ($socials as $key => $name) {
                    if (site("social.{$key}")) { $hasSocials = true; break; }
                }
                ?>
                <?php if ($hasSocials): ?>
                <div class="flex space-x-4">
                    <?php foreach ($socials as $key => $name): ?>
                        <?php if (site("social.{$key}")): ?>
                        <a href="<?= e(site("social.{$key}")) ?>" target="_blank" rel="noopener" class="text-gray-500 hover:text-white text-sm transition-colors"><?= $name ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4">
                <p class="text-xs" style="color: var(--color-muted); opacity: 0.5;">
                    <a href="https://easeo.nl" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;"><?= t('footer_powered_by') ?></a>
                </p>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/cookie-consent.php'; ?>

    <script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
        var menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });
    </script>
</body>
</html>
