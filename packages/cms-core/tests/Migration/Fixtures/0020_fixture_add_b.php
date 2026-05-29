<?php
declare(strict_types=1);

use Easeo\Cms\Migration\MigrationInterface;

return new class implements MigrationInterface {
    public function version(): int { return 20; }
    public function description(): string { return 'Fixture: write data/b.json'; }

    public function up(string $dataDir): void
    {
        $tmp = "$dataDir/b.json.tmp";
        $dest = "$dataDir/b.json";
        file_put_contents($tmp, json_encode(['fixture-b'], JSON_PRETTY_PRINT));
        rename($tmp, $dest);
    }
};
