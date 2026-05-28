<?php
/**
 * Fase A bootstrap — laadt alle legacy engines.
 * Wordt verwijderd aan einde Fase B (wanneer alle engines PSR-4 zijn).
 */
require_once dirname(__DIR__, 4) . '/tools/legacy-bridge.php';
$legacyDir = __DIR__;
$engines = [
    // Alle Categorie-3 templates zijn verplaatst naar templates/layout/ (B15).
    // Dit array is leeg; de foreach hieronder is een no-op.
    // Wordt verwijderd in B16 (legacy-bridge cleanup).
];
foreach ($engines as $engine) {
    $path = $legacyDir . '/' . $engine;
    if (is_file($path)) {
        require_once $path;
    }
}
