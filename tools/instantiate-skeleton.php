<?php
declare(strict_types=1);

// Instantiate the apps/_skeleton/ template into an output directory with
// placeholders substituted. Used during klant-site cutover (Plans 10, 11).
//
// Usage: php tools/instantiate-skeleton.php \
//          --site="RWW Bouw" \
//          --domain=rwwbouw.nl \
//          --reviewer=ardo-handle \
//          --backstop=nick-aldewereld \
//          --output=/tmp/rww-bootstrap/

const REQUIRED_ARGS = ['site', 'domain', 'reviewer', 'backstop', 'output'];

function parseArgs(array $argv): array
{
    $parsed = [];
    foreach (array_slice($argv, 1) as $a) {
        if (preg_match('/^--([^=]+)=(.*)$/', $a, $m)) {
            $parsed[$m[1]] = $m[2];
        }
    }
    return $parsed;
}

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERROR: $msg\n");
    exit($code);
}

function copyDir(string $src, string $dst): void
{
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $relPath = substr($item->getPathname(), strlen($src) + 1);
        $target = "$dst/$relPath";
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }
            copy($item->getPathname(), $target);
            // preserve executable bit
            if (is_executable($item->getPathname())) {
                chmod($target, 0755);
            }
        }
    }
}

function substitutePlaceholders(string $dir, array $values): void
{
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) {
            continue;
        }
        // skip binary file types
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico'], true)) {
            continue;
        }
        $content = file_get_contents($file->getPathname());
        $original = $content;
        foreach ($values as $key => $val) {
            $content = str_replace('{{' . $key . '}}', $val, $content);
        }
        if ($content !== $original) {
            file_put_contents($file->getPathname(), $content);
        }
    }
}

// --- main ---

$args = parseArgs($argv);
foreach (REQUIRED_ARGS as $r) {
    if (!isset($args[$r]) || $args[$r] === '') {
        fail("missing required arg --$r");
    }
}

$skeletonDir = realpath(__DIR__ . '/../apps/_skeleton');
if ($skeletonDir === false || !is_dir($skeletonDir)) {
    fail('cannot locate apps/_skeleton/');
}

$outputDir = $args['output'];

// Refuse to clobber a non-empty existing dir
if (is_dir($outputDir)) {
    $items = array_diff(scandir($outputDir), ['.', '..']);
    if (!empty($items)) {
        fail("output dir $outputDir is not empty — refusing to overwrite");
    }
}

// 1. Copy skeleton → output
copyDir($skeletonDir, $outputDir);

// 2. Rename site.template.json.example → site.template.json
$exampleFile = "$outputDir/site.template.json.example";
if (file_exists($exampleFile)) {
    rename($exampleFile, "$outputDir/site.template.json");
}

// 3. Substitute placeholders
$values = [
    'SITE_NAME' => $args['site'],
    'SITE_DOMAIN' => $args['domain'],
    'SITE_REPO' => preg_replace('/[^a-z0-9-]/', '-', strtolower($args['site'])) . '-website',
    'REVIEWER_HANDLE' => $args['reviewer'],
    'BACKSTOP_HANDLE' => $args['backstop'],
];
substitutePlaceholders($outputDir, $values);

echo "Skeleton instantiated at $outputDir\n";
echo "  site:     {$values['SITE_NAME']}\n";
echo "  domain:   {$values['SITE_DOMAIN']}\n";
echo "  reviewer: {$values['REVIEWER_HANDLE']}\n";
echo "  backstop: {$values['BACKSTOP_HANDLE']}\n";
echo "\nNext steps:\n";
echo "  cd $outputDir\n";
echo "  composer install\n";
echo "  git init && git add . && git commit -m 'Initial commit from skeleton'\n";

exit(0);
