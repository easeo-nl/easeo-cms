#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Easeo\Tools\RenameEngine;

$opts = getopt('', ['engine:', 'mapping:', 'dry-run', 'help']);

if (isset($opts['help']) || !isset($opts['engine'])) {
    echo "Gebruik: php tools/rename-engine.php --engine=<name> [--mapping=<file>] [--dry-run]\n";
    echo "Voorbeeld: php tools/rename-engine.php --engine=brand --dry-run\n";
    exit(isset($opts['help']) ? 0 : 1);
}

$engine = $opts['engine'];
$mappingFile = $opts['mapping'] ?? __DIR__ . '/mappings/' . $engine . '.json';
$dryRun = isset($opts['dry-run']);

if (!is_file($mappingFile)) {
    fwrite(STDERR, "Mapping-file niet gevonden: $mappingFile\n");
    exit(1);
}

$mapping = json_decode(file_get_contents($mappingFile), true, flags: JSON_THROW_ON_ERROR);

$root = dirname(__DIR__);
$renamer = new RenameEngine(
    legacyDir: $root . '/packages/cms-core/src/legacy',
    targetBase: $root . '/packages/cms-core/src',
    callerDirs: [
        $root . '/apps/easeo-website/public',
        $root . '/packages/cms-core/templates',
        $root . '/packages/cms-core/beheer',
        $root . '/packages/cms-core/src/legacy',
    ],
);

$result = $renamer->rename($mapping, $dryRun);

if ($dryRun) {
    echo "DRY-RUN — geen wijzigingen geschreven\n";
    echo "Target: {$result['target_file']}\n";
    echo "Preview:\n{$result['target_code_preview']}\n";
    echo "\nCaller-changes: " . count($result['caller_changes']) . " file(s)\n";
    foreach ($result['caller_changes'] as $file => $_) {
        echo "  - $file\n";
    }
} else {
    echo "Rename voltooid voor engine '$engine'.\n";
}
