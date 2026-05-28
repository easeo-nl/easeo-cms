<?php
/**
 * Fase A bootstrap — laadt alle legacy engines.
 * Wordt verwijderd aan einde Fase B (wanneer alle engines PSR-4 zijn).
 */
require_once dirname(__DIR__, 4) . '/tools/legacy-bridge.php';
$legacyDir = __DIR__;
$engines = [
    'rate-limiter.php',
    'legal.php',
    'cookie-consent.php',
    'media-engine.php',
    'navigation.php',
    'structured-data.php',
    // header.php, footer.php, tracking-head.php, tracking-body.php worden
    // NIET hier geladen: zij produceren HTML-output en worden expliciet
    // included op de juiste positie in elke entry-file.
];
foreach ($engines as $engine) {
    $path = $legacyDir . '/' . $engine;
    if (is_file($path)) {
        require_once $path;
    }
}
