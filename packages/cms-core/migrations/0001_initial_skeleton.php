<?php
declare(strict_types=1);

use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int { return 1; }
    public function description(): string { return 'Initial skeleton — ensure data/ has the baseline files'; }

    public function up(string $dataDir): void
    {
        // No-op safety net. A site that has been running site_setup correctly
        // already has data/ populated; this migration just bumps the version
        // marker so the runner doesn't try to re-apply it on every boot.
    }
};
