<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Homepage with template sections
 */
require_once __DIR__ . '/../vendor/autoload.php';
\Easeo\Cms\Constants::bootstrap(dirname(__DIR__));
ContentRepository::checkSetup();
$home = ContentRepository::pageContent('home');
$pageTitle = ($home['meta_title'] ?? 'Home') . ' | ' . ContentRepository::siteValue('company.name', 'EASEO');
$metaDescription = $home['meta_description'] ?? '';
require_once EASEO_TEMPLATES . '/layout/header.php';
// Hero section via template
$data = ['titel' => $home['hero_titel'] ?? 'Welkom', 'tekst' => $home['hero_tekst'] ?? '', 'afbeelding' => $home['hero_afbeelding'] ?? '', 'knop_tekst' => $home['hero_knop_tekst'] ?? '', 'knop_url' => $home['hero_knop_url'] ?? '/contact'];
include __DIR__ . '/templates/hero.php';
// Section 1
if (!empty($home['sectie1_titel'])) {
    $data = ['titel' => $home['sectie1_titel'], 'tekst' => $home['sectie1_tekst'] ?? '', 'achtergrond' => 'white', 'gecentreerd' => true];
    include __DIR__ . '/templates/text-block.php';
}
// Section 2
if (!empty($home['sectie2_titel'])) {
    $data = ['titel' => $home['sectie2_titel'], 'tekst' => $home['sectie2_tekst'] ?? '', 'achtergrond' => 'light', 'gecentreerd' => true];
    include __DIR__ . '/templates/text-block.php';
}
// Latest blog posts
$data = ['titel' => Translator::translate('homepage_latest_posts_title'), 'tekst' => '', 'aantal' => 3];
include __DIR__ . '/templates/blog-latest.php';
// CTA section
$data = ['titel' => Translator::translate('homepage_cta_title'), 'tekst' => Translator::translate('homepage_cta_text'), 'knop_tekst' => Translator::translate('homepage_cta_button'), 'knop_url' => '/contact', 'achtergrond' => 'primary'];
include __DIR__ . '/templates/cta-block.php';
require_once EASEO_TEMPLATES . '/layout/footer.php';
