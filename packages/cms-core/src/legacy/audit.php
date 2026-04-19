<?php
/**
 * EASEO CMS — Audit logging
 */

function audit_log(string $action, string $details = '', string $user = ''): void {
    $logFile = EASEO_DATA . '/audit.log';

    if (empty($user) && isset($_SESSION['easeo_admin'])) {
        $user = $_SESSION['easeo_admin']['naam'] ?? $_SESSION['easeo_admin']['email'] ?? 'onbekend';
    }
    if (empty($user)) {
        $user = 'systeem';
    }

    $entry = json_encode([
        'datum' => date('Y-m-d H:i:s'),
        'gebruiker' => $user,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'actie' => $action,
        'details' => $details,
    ], JSON_UNESCAPED_UNICODE) . "\n";

    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    // Rotate if > 1MB
    if (file_exists($logFile) && filesize($logFile) > 1048576) {
        $backup = EASEO_DATA . '/audit.log.old';
        if (file_exists($backup)) unlink($backup);
        rename($logFile, $backup);
    }
}

function read_audit_log(int $limit = 100, int $offset = 0): array {
    $logFile = EASEO_DATA . '/audit.log';
    if (!file_exists($logFile)) return [];

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // newest first

    $entries = [];
    $count = 0;
    foreach ($lines as $line) {
        $entry = json_decode($line, true);
        if (!$entry) continue;

        if ($count >= $offset) {
            $entries[] = $entry;
        }
        $count++;

        if (count($entries) >= $limit) break;
    }

    return $entries;
}
