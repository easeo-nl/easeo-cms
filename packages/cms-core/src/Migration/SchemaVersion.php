<?php
declare(strict_types=1);

namespace Easeo\Cms\Migration;

final class SchemaVersion
{
    public function __construct(private readonly string $dataDir) {}

    public function current(): int
    {
        $file = $this->path();
        if (!is_file($file)) {
            return 0;
        }
        $raw = trim((string) file_get_contents($file));
        return ctype_digit($raw) ? (int) $raw : 0;
    }

    public function set(int $version): void
    {
        $file = $this->path();
        $tmp = $file . '.tmp';
        file_put_contents($tmp, (string) $version, LOCK_EX);
        rename($tmp, $file); // atomic on POSIX
    }

    private function path(): string
    {
        return $this->dataDir . '/.schema-version';
    }
}
