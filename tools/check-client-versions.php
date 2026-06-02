#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * check-client-versions — admin CLI
 *
 * Vergelijkt alle bekende klant-repos hun composer.lock-pin van easeo/cms-core
 * met de huidige stable versie op Packagist.
 *
 * Usage:
 *   php tools/check-client-versions.php
 *   CLIENT_REPOS="easeo-nl/rww-website,easeo-nl/custom-site" php tools/check-client-versions.php
 *
 * Configure repo list via env CLIENT_REPOS (comma-separated GitHub slugs)
 * or fall back to the hardcoded list below.
 */

$defaultRepos = [
    'easeo-nl/rww-website',
    'easeo-nl/qpmarketing-website',
    'easeo-nl/easeo-website',
];

$envRepos = getenv('CLIENT_REPOS');
if ($envRepos !== false && $envRepos !== '') {
    $repos = array_filter(array_map('trim', explode(',', $envRepos)));
} else {
    $repos = $defaultRepos;
}

// Fetch latest stable version from Packagist
echo "Fetching latest easeo/cms-core version from Packagist...\n";

$packagistUrl = 'https://packagist.org/packages/easeo/cms-core.json';
$ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
$json = @file_get_contents($packagistUrl, false, $ctx);

$latest = null;
if ($json !== false) {
    $data     = json_decode($json, true);
    $versions = $data['package']['versions'] ?? [];
    // Find highest stable (x.y.z without pre-release suffix)
    foreach ($versions as $v => $_) {
        if (preg_match('/^\d+\.\d+\.\d+$/', $v)) {
            if ($latest === null || version_compare($v, $latest, '>')) {
                $latest = $v;
            }
        }
    }
}

echo 'Latest stable: ' . ($latest ?? '(unknown — Packagist onbereikbaar)') . "\n\n";

$hasOutdated = false;

foreach ($repos as $repo) {
    $lockUrl = "https://raw.githubusercontent.com/$repo/main/composer.lock";
    $ctx     = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $body    = @file_get_contents($lockUrl, false, $ctx);

    if ($body === false) {
        printf("%-42s  %s\n", $repo, 'ONBEREIKBAAR (404 of geen toegang)');
        continue;
    }

    $lock    = json_decode($body, true);
    $pkgs    = $lock['packages'] ?? [];
    $pinned  = null;

    foreach ($pkgs as $p) {
        if (($p['name'] ?? null) === 'easeo/cms-core') {
            $pinned = $p['version'] ?? '?';
            break;
        }
    }

    if ($pinned === null) {
        printf("%-42s  %s\n", $repo, 'geen easeo/cms-core dependency');
        continue;
    }

    if ($latest !== null && $pinned !== $latest) {
        $hasOutdated = true;
        printf("%-42s  VEROUDERD: %s  (latest: %s)\n", $repo, $pinned, $latest);
    } else {
        printf("%-42s  up-to-date (%s)\n", $repo, $pinned);
    }
}

echo "\n";
if ($hasOutdated) {
    echo "Er zijn verouderde repos. Stuur een Dependabot PR of update handmatig:\n";
    echo "  composer require easeo/cms-core:^<nieuw-versienummer>\n";
    exit(1);
}
exit(0);
