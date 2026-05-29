<?php
declare(strict_types=1);

use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int { return 10; }
    public function description(): string { return 'Fixture: write data/a.json'; }

    public function up(string $dataDir): void
    {
        $tmp = "$dataDir/a.json.tmp";
        $dest = "$dataDir/a.json";
        file_put_contents($tmp, json_encode(['fixture-a'], JSON_PRETTY_PRINT));
        rename($tmp, $dest);
    }
};
