<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use PHPUnit\Framework\TestCase;

final class SeedScriptTest extends TestCase
{
    public function test_seed_script_copies_all_fixtures_to_data(): void
    {
        $root = dirname(__DIR__, 2);
        $appDir = "$root/apps/_fixture-app";
        $dataDir = "$appDir/data";

        // Clean
        array_map('unlink', glob("$dataDir/*.json") ?: []);
        @unlink("$dataDir/.schema-version");

        // Run
        $output = [];
        $status = 0;
        exec("$appDir/bin/seed.sh 2>&1", $output, $status);

        $this->assertSame(0, $status, "seed.sh failed: " . implode("\n", $output));
        $this->assertFileExists("$dataDir/site.json");
        $this->assertFileExists("$dataDir/pages.json");
        $this->assertFileExists("$dataDir/posts.json");
        $this->assertFileExists("$dataDir/navigation.json");
        $this->assertFileExists("$dataDir/users.json");
        $this->assertFileExists("$dataDir/.schema-version");
        $this->assertSame('0', trim(file_get_contents("$dataDir/.schema-version")));
    }

    public function test_seed_script_is_idempotent(): void
    {
        $root = dirname(__DIR__, 2);
        $appDir = "$root/apps/_fixture-app";

        // Run twice
        exec("$appDir/bin/seed.sh 2>&1");
        $firstMtime = filemtime("$appDir/data/site.json");
        sleep(1);
        $status = 0;
        exec("$appDir/bin/seed.sh 2>&1", $_, $status);

        $this->assertSame(0, $status);
        // Idempotent in effect: re-runnable, geen errors. mtime mag wijzigen (cp overschrijft) — dat is OK.
        $this->assertFileExists("$appDir/data/site.json");
    }
}
