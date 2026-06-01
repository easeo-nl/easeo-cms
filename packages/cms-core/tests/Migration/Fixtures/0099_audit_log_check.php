<?php
declare(strict_types=1);

use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int { return 99; }
    public function description(): string { return 'Audit-log smoke fixture'; }
    public function up(string $dataDir): void
    {
        file_put_contents("$dataDir/audit-fixture-touched", '1');
    }
};
