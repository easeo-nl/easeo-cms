#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$args   = array_slice($argv, 1);
$apply  = in_array('--apply', $args, true);
$appRoot = null;
foreach ($args as $a) {
    if (str_starts_with($a, '--app=')) {
        $appRoot = substr($a, 6);
    }
}
if ($appRoot === null) {
    // Default to apps/easeo-website relative to repo root
    $appRoot = realpath(__DIR__ . '/../apps/easeo-website') ?: null;
}

if ($appRoot === null || !is_dir($appRoot)) {
    fwrite(STDERR, "Usage: php tools/migrate-site-json-to-split.php [--apply] [--app=path/to/site-app]\n\n");
    fwrite(STDERR, "Default app-root is apps/easeo-website. Use --apply to write files (otherwise dry-run).\n");
    exit(2);
}

$dataDir = $appRoot . '/data';
if (!is_dir($dataDir)) {
    fwrite(STDERR, "ERROR: $dataDir does not exist\n");
    exit(3);
}

$migrator = new \Easeo\Cms\Config\SiteJsonMigrator($dataDir, $appRoot);

if ($apply) {
    $result = $migrator->apply();
    echo "MIGRATION APPLIED.\n\n";
} else {
    $result = $migrator->plan();
    echo "DRY-RUN (no files written). Run with --apply to commit.\n\n";
}

echo "App root:        $appRoot\n";
echo "site.json found: " . ($result['site_json_exists'] ? 'yes' : 'no — nothing to migrate') . "\n";

if ($result['site_json_exists']) {
    echo "Backup target:   {$result['backup_path']}\n";
    echo ".env target:     {$result['env_path']}" . ($result['env_exists'] ? ' (exists; will append)' : ' (new file)') . "\n";
    echo "site.config.json: {$result['site_config_path']}" . ($result['site_config_json_exists'] ? ' (exists; will skip)' : ' (new file)') . "\n\n";

    echo "Secrets detected (" . count($result['secrets_found']) . "):\n";
    foreach (array_keys($result['secrets_found']) as $envVar) {
        echo "  - $envVar\n";
    }
    echo "\nSite-config keys retained: " . implode(', ', $result['site_config_keys']) . "\n";

    if (!empty($result['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($result['warnings'] as $w) {
            echo "  ! $w\n";
        }
    }

    if ($apply && !empty($result['actions'])) {
        echo "\nActions taken:\n";
        foreach ($result['actions'] as $a) {
            echo "  + $a\n";
        }
    }
}

if (!$apply) {
    echo "\nNext step: re-run with --apply to commit, OR edit the appended .env block to uncomment + verify, OR pass --app=path/to/custom-app-root.\n";
}

exit(0);
