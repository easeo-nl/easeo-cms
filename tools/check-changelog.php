<?php
declare(strict_types=1);

// Usage: php check-changelog.php <changelog-file> <version>
// Exits 0 if changelog has a `## [<version>]` heading, non-zero otherwise.

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php check-changelog.php <changelog-file> <version>\n");
    exit(2);
}

[$_, $changelogPath, $version] = $argv;

if (!is_readable($changelogPath)) {
    fwrite(STDERR, "Cannot read $changelogPath\n");
    exit(3);
}

// Strip leading 'v' if present (so tags v1.2.3 → 1.2.3)
$version = ltrim($version, 'v');
$content = file_get_contents($changelogPath);

if (preg_match('/^##\s*\[' . preg_quote($version, '/') . '\]/m', $content) !== 1) {
    fwrite(STDERR, "ERROR: no '## [$version]' entry found in $changelogPath\n");
    fwrite(STDERR, "Add a section before tagging:\n\n## [$version] - YYYY-MM-DD\n\n### Added/Changed/Fixed\n- ...\n");
    exit(1);
}

echo "OK: changelog has entry for $version\n";
exit(0);
