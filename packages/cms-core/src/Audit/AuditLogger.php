<?php
declare(strict_types=1);

namespace Easeo\Cms\Audit;

final class AuditLogger
{
    public static function log(string $action, string $details = '', string $user = ''): void
    {
        $logFile = self::dataPath() . '/audit.log';

        if ($user === '' && isset($_SESSION['easeo_admin'])) {
            $user = $_SESSION['easeo_admin']['naam'] ?? $_SESSION['easeo_admin']['email'] ?? 'onbekend';
        }
        if ($user === '') {
            $user = 'systeem';
        }

        $entry = json_encode([
            'datum'     => date('Y-m-d H:i:s'),
            'gebruiker' => $user,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'actie'     => $action,
            'details'   => $details,
        ], JSON_UNESCAPED_UNICODE) . "\n";

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

        if (file_exists($logFile) && filesize($logFile) > 1048576) {
            $backup = self::dataPath() . '/audit.log.old';
            if (file_exists($backup)) {
                unlink($backup);
            }
            rename($logFile, $backup);
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function read(int $limit = 100, int $offset = 0): array
    {
        $logFile = self::dataPath() . '/audit.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_reverse($lines);

        $entries = [];
        $count = 0;
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!is_array($entry)) {
                continue;
            }
            if ($count >= $offset) {
                $entries[] = $entry;
            }
            $count++;
            if (count($entries) >= $limit) {
                break;
            }
        }
        return $entries;
    }

    private static function dataPath(): string
    {
        $env = getenv('EASEO_DATA');
        if ($env !== false && $env !== '') {
            return $env;
        }
        return defined('EASEO_DATA') ? constant('EASEO_DATA') : '';
    }
}
