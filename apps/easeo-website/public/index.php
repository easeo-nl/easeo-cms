<?php
/**
 * EASEO CMS — Homepage with template sections
 */
require_once __DIR__ . '/includes/content.php';
check_setup();

$home = page_content('home');
$pageTitle = ($home['meta_title'] ?? 'Home') . ' | ' . site('company.name', 'EASEO');
$metaDescription = $home['meta_description'] ?? '';

require_once __DIR__ . '/includes/header.php';

// Hero section via template
$data = [
    'titel' => $home['hero_titel'] ?? 'Welkom',
    'tekst' => $home['hero_tekst'] ?? '',
    'afbeelding' => $home['hero_afbeelding'] ?? '',
    'knop_tekst' => $home['hero_knop_tekst'] ?? '',
    'knop_url' => $home['hero_knop_url'] ?? '/contact',
];
include __DIR__ . '/templates/hero.php';

// Section 1
if (!empty($home['sectie1_titel'])):
    $data = [
        'titel' => $home['sectie1_titel'],
        'tekst' => $home['sectie1_tekst'] ?? '',
        'achtergrond' => 'white',
        'gecentreerd' => true,
    ];
    include __DIR__ . '/templates/text-block.php';
endif;

// Section 2
if (!empty($home['sectie2_titel'])):
    $data = [
        'titel' => $home['sectie2_titel'],
        'tekst' => $home['sectie2_tekst'] ?? '',
        'achtergrond' => 'light',
        'gecentreerd' => true,
    ];
    include __DIR__ . '/templates/text-block.php';
endif;

// Latest blog posts
$data = [
    'titel' => t('homepage_latest_posts_title'),
    'tekst' => '',
    'aantal' => 3,
];
include __DIR__ . '/templates/blog-latest.php';

// CTA section
$data = [
    'titel' => t('homepage_cta_title'),
    'tekst' => t('homepage_cta_text'),
    'knop_tekst' => t('homepage_cta_button'),
    'knop_url' => '/contact',
    'achtergrond' => 'primary',
];
include __DIR__ . '/templates/cta-block.php';

require_once __DIR__ . '/includes/footer.php';
