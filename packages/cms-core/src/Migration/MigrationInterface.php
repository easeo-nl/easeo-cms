<?php
declare(strict_types=1);

namespace Easeo\Cms\Migration;

/**
 * Forward-only migration. Implementations live under
 * packages/cms-core/migrations/NNNN_*.php and return an anonymous class
 * implementing this interface.
 */
interface MigrationInterface
{
    public function version(): int;
    public function description(): string;

    /**
     * Apply the migration to the data dir. The runner provides the data-dir
     * path so the migration can read/write JSON files atomically.
     */
    public function up(string $dataDir): void;
}
