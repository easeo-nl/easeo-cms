<?php
/**
 * EASEO CMS — Install bootstrap
 * Checks that data files exist and creates them from templates if needed.
 * Run this once after fresh deployment, or include from index.php.
 */

$root = __DIR__;
$dataDir = $root . '/data';

// Ensure data directory exists
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);
if (!is_dir($dataDir . '/submissions')) mkdir($dataDir . '/submissions', 0755, true);

// Ensure image directories exist
if (!is_dir($root . '/images/uploads')) mkdir($root . '/images/uploads', 0755, true);
if (!is_dir($root . '/images/thumbs')) mkdir($root . '/images/thumbs', 0755, true);

// Default data files
$defaults = [
    'site.json' => $root . '/site.template.json',
    'pages.json' => null,
    'content.json' => null,
    'posts.json' => null,
    'forms.json' => null,
    'media.json' => null,
    'navigation.json' => null,
    'legal.json' => null,
    'redirects.json' => null,
    'users.json' => null,
    'login_attempts.json' => null,
];

$defaultContents = [
    'pages.json' => '{"pages":[]}',
    'content.json' => '{}',
    'posts.json' => '{"settings":{"posts_per_page":9,"seo_title":"","seo_description":""},"posts":[],"categories":["nieuws","tips","cases"]}',
    'forms.json' => '{"forms":[]}',
    'media.json' => '{"files":[]}',
    'navigation.json' => '{"main":[],"footer":[]}',
    'legal.json' => '{"privacy":{"seo_title":"","seo_description":"","content":"","last_updated":""},"voorwaarden":{"seo_title":"","seo_description":"","content":"","last_updated":""},"cookies":{"seo_title":"","seo_description":"","content":"","last_updated":""}}',
    'redirects.json' => '{"redirects":[]}',
    'users.json' => '{"users":[]}',
    'login_attempts.json' => '{}',
];

foreach ($defaults as $file => $templatePath) {
    $target = $dataDir . '/' . $file;
    if (!file_exists($target)) {
        if ($templatePath && file_exists($templatePath)) {
            copy($templatePath, $target);
        } elseif (isset($defaultContents[$file])) {
            file_put_contents($target, $defaultContents[$file]);
        }
    }
}

// Create .htaccess to protect data directory
$htaccess = $dataDir . '/.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
}

// Redirect to setup if not yet configured
if (php_sapi_name() !== 'cli') {
    header('Location: /setup.php');
    exit;
}

echo "EASEO CMS — Install complete. Data files initialized.\n";
