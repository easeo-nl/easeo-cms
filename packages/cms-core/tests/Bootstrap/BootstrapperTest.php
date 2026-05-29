<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Bootstrap;

use Easeo\Cms\Bootstrap\Bootstrapper;
use PHPUnit\Framework\TestCase;

final class BootstrapperTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/bootstrapper-test-' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function test_creates_default_skeleton_files(): void
    {
        $bootstrapper = new Bootstrapper($this->tmpDir);
        $created = $bootstrapper->bootstrap();

        $expected = ['pages.json', 'posts.json', 'navigation.json', 'forms.json', 'media.json', 'legal.json', 'users.json'];
        foreach ($expected as $name) {
            $this->assertContains($name, $created, "$name should be listed as created");
            $this->assertFileExists($this->tmpDir . '/data/' . $name, "$name should exist on disk");
        }
    }

    public function test_skips_already_existing_files(): void
    {
        $dataDir = $this->tmpDir . '/data';
        mkdir($dataDir, 0755, true);
        file_put_contents("$dataDir/pages.json", json_encode(['existing' => true]));

        $bootstrapper = new Bootstrapper($this->tmpDir);
        $created = $bootstrapper->bootstrap();

        $this->assertNotContains('pages.json', $created, 'Existing pages.json should not be recreated');

        // Content must remain unchanged
        $content = json_decode((string) file_get_contents("$dataDir/pages.json"), true);
        $this->assertSame(['existing' => true], $content);
    }

    public function test_copies_template_when_present(): void
    {
        $templateContent = json_encode(['name' => 'My Site', 'locale' => 'nl']);
        file_put_contents($this->tmpDir . '/site.template.json', $templateContent);

        $bootstrapper = new Bootstrapper($this->tmpDir);
        $created = $bootstrapper->bootstrap();

        $this->assertContains('site.json', $created, 'site.json should be created from template');
        $this->assertFileExists($this->tmpDir . '/data/site.json');
        $this->assertSame($templateContent, file_get_contents($this->tmpDir . '/data/site.json'));
    }

    public function test_no_site_json_when_template_absent(): void
    {
        $bootstrapper = new Bootstrapper($this->tmpDir);
        $created = $bootstrapper->bootstrap();

        $this->assertNotContains('site.json', $created);
        $this->assertFileDoesNotExist($this->tmpDir . '/data/site.json');
    }

    public function test_idempotent_second_run_creates_nothing(): void
    {
        $bootstrapper = new Bootstrapper($this->tmpDir);
        $bootstrapper->bootstrap(); // first run

        $second = $bootstrapper->bootstrap(); // second run
        $this->assertSame([], $second, 'Second run should create no files');
    }

    public function test_creates_data_dir_if_missing(): void
    {
        $appRoot = $this->tmpDir . '/subapp';
        mkdir($appRoot, 0755, true);

        $bootstrapper = new Bootstrapper($appRoot);
        $bootstrapper->bootstrap();

        $this->assertDirectoryExists($appRoot . '/data');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = "$dir/$entry";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
