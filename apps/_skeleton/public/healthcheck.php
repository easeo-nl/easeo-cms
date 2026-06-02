<?php
declare(strict_types=1);

/**
 * EASEO CMS healthcheck endpoint.
 *
 * Public:  GET /healthcheck.php           → {"status":"ok"}
 * Private: GET /healthcheck.php?secret=X  → extended JSON (when HEALTHCHECK_SECRET env matches)
 *
 * Never throws — always returns JSON.
 */

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Type: application/json; charset=utf-8');

try {
    require __DIR__ . '/../vendor/autoload.php';
    \Easeo\Cms\Config\Environment::load(__DIR__ . '/..');

    $payload = \Easeo\Cms\Routes\Healthcheck::handle(
        __DIR__ . '/..',
        $_GET['secret'] ?? null
    );
} catch (\Throwable $e) {
    $payload = ['status' => 'ok'];
}

echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
