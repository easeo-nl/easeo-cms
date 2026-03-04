<?php
/**
 * EASEO CMS — Contact page with dynamic form
 */
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/form-engine.php';
check_setup();

$contactData = page_content('contact');
$pageTitle = ($contactData['meta_title'] ?? 'Contact') . ' | ' . site('company.name', 'EASEO');
$metaDescription = $contactData['meta_description'] ?? '';
$formId = $contactData['formulier_id'] ?? 'contact';

require_once __DIR__ . '/includes/header.php';
?>

<section class="py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Form -->
            <div>
                <h1 class="text-3xl font-display font-bold text-dark mb-4"><?= e($contactData['titel'] ?? 'Contact') ?></h1>
                <?php if (!empty($contactData['intro_tekst'])): ?>
                <p class="text-muted mb-6"><?= e($contactData['intro_tekst']) ?></p>
                <?php endif; ?>

                <?= render_form($formId) ?>
            </div>

            <!-- Contact info -->
            <div>
                <div class="bg-light rounded-lg p-8">
                    <h2 class="text-xl font-display font-bold text-dark mb-6">Contactgegevens</h2>

                    <div class="space-y-4">
                        <?php if (site('company.address')): ?>
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-primary mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <div>
                                <p class="font-medium text-dark"><?= e(site('company.address')) ?></p>
                                <p class="text-muted"><?= e(site('company.postcode')) ?> <?= e(site('company.city')) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (site('company.email')): ?>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a href="mailto:<?= e(site('company.email')) ?>" class="text-primary hover:underline"><?= e(site('company.email')) ?></a>
                        </div>
                        <?php endif; ?>

                        <?php if (site('company.phone')): ?>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <a href="tel:<?= e(site('company.phone')) ?>" class="text-primary hover:underline"><?= e(site('company.phone')) ?></a>
                        </div>
                        <?php endif; ?>

                        <?php if (site('company.kvk')): ?>
                        <div class="flex items-center gap-3 text-sm text-muted">
                            <span>KVK: <?= e(site('company.kvk')) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($contactData['kaart_embed'])): ?>
                <div class="mt-6 rounded-lg overflow-hidden">
                    <?= $contactData['kaart_embed'] ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
