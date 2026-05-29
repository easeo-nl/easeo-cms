<?php
declare(strict_types=1);

namespace Easeo\Cms\Migration;

final class Runner
{
    private readonly SchemaVersion $version;

    public function __construct(
        private readonly string $dataDir,
        private readonly string $migrationsDir
    ) {
        $this->version = new SchemaVersion($dataDir);
    }

    /**
     * Run any pending migrations. Returns the list of migration versions
     * that were applied (empty if nothing to do or another request is
     * already migrating).
     *
     * @return list<int>
     */
    public function runPending(): array
    {
        $current = $this->version->current();
        $pending = $this->loadPending($current);

        if (empty($pending)) {
            return [];
        }

        $lockFile = $this->dataDir . '/.migration.lock';
        $lock = @fopen($lockFile, 'c');
        if ($lock === false) {
            return [];
        }

        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                // Another request is already migrating
                return [];
            }

            $applied = [];
            try {
                foreach ($pending as $migration) {
                    $migration->up($this->dataDir);
                    $this->version->set($migration->version());
                    $applied[] = $migration->version();
                }
            } finally {
                flock($lock, LOCK_UN);
            }

            return $applied;
        } finally {
            fclose($lock);
            @unlink($lockFile);
        }
    }

    /**
     * @return list<MigrationInterface>
     */
    private function loadPending(int $current): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }

        $files = glob($this->migrationsDir . '/[0-9]*.php') ?: [];
        sort($files, SORT_NATURAL);

        $pending = [];
        foreach ($files as $file) {
            $migration = require $file;
            if (!$migration instanceof MigrationInterface) {
                throw new \RuntimeException(
                    "Migration file $file did not return a MigrationInterface instance"
                );
            }
            if ($migration->version() > $current) {
                $pending[] = $migration;
            }
        }

        // Stable ordering by version (in case file-name lexical sort drifts)
        usort($pending, fn($a, $b) => $a->version() <=> $b->version());
        return $pending;
    }
}
