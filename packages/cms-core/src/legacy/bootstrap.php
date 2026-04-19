<?php
/**
 * Fase A bootstrap — laadt alle legacy engines.
 * Wordt verwijderd aan einde Fase B (wanneer alle engines PSR-4 zijn).
 */
$legacyDir = __DIR__;
$engines = [
    'content.php',        // eerst — andere engines requiren dit
    'lang.php',
    'brand.php',
    'audit.php',
    'rate-limiter.php',
    'mailer.php',
    'form-engine.php',
    'blog-engine.php',
    'legal.php',
    'cookie-consent.php',
    'media-engine.php',
    'navigation.php',
    'structured-data.php',
    'tracking-head.php',
    'tracking-body.php',
    'header.php',
    'footer.php',
];
foreach ($engines as $engine) {
    $path = $legacyDir . '/' . $engine;
    if (is_file($path)) {
        require_once $path;
    }
}
