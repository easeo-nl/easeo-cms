<?php
declare(strict_types=1);

namespace Easeo\Tools\Tests;

use PHPUnit\Framework\TestCase;

final class InstantiateSkeletonTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/instantiate-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->rmrf($this->tmpDir);
        }
    }

    private function rmrf(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private function runCli(array $args): array
    {
        $script = dirname(__DIR__) . '/instantiate-skeleton.php';
        $argString = implode(' ', array_map('escapeshellarg', $args));
        $output = [];
        $status = 0;
        exec("php $script $argString 2>&1", $output, $status);
        return ['output' => implode("\n", $output), 'status' => $status];
    }

    public function test_fails_when_required_args_missing(): void
    {
        $result = $this->runCli(['--site=foo']);
        $this->assertNotSame(0, $result['status']);
        $this->assertStringContainsString('domain', $result['output']);
    }

    public function test_creates_output_dir_with_skeleton_structure(): void
    {
        $result = $this->runCli([
            '--site=demo',
            '--domain=demo.example',
            '--reviewer=alice',
            '--backstop=bob',
            "--output={$this->tmpDir}",
        ]);

        $this->assertSame(0, $result['status'], $result['output']);

        // Critical files exist
        $this->assertFileExists("{$this->tmpDir}/composer.json");
        $this->assertFileExists("{$this->tmpDir}/public/index.php");
        $this->assertFileExists("{$this->tmpDir}/public/.htaccess");
        $this->assertFileExists("{$this->tmpDir}/site.template.json");
        $this->assertFileExists("{$this->tmpDir}/.gitignore");
        $this->assertFileExists("{$this->tmpDir}/bin/easeo-doctor");
        $this->assertFileExists("{$this->tmpDir}/.github/workflows/deploy.yml");
        $this->assertFileExists("{$this->tmpDir}/.github/workflows/pr-check.yml");
        $this->assertFileExists("{$this->tmpDir}/.github/workflows/dependabot-comment.yml");
        $this->assertFileExists("{$this->tmpDir}/.github/dependabot.yml");
        $this->assertFileExists("{$this->tmpDir}/.github/PULL_REQUEST_TEMPLATE.md");
        $this->assertFileExists("{$this->tmpDir}/docs/DEVELOPER.md");
        $this->assertFileExists("{$this->tmpDir}/docs/DEPLOY.md");
        $this->assertFileExists("{$this->tmpDir}/docs/adr/0001-thin-site-app-pattern.md");
        $this->assertFileExists("{$this->tmpDir}/README.md");

        // site.template.json renamed from .example
        $this->assertFileDoesNotExist("{$this->tmpDir}/site.template.json.example");
    }

    public function test_substitutes_all_placeholders(): void
    {
        $result = $this->runCli([
            '--site=Demo Site',
            '--domain=demo.example',
            '--reviewer=alice',
            '--backstop=bob',
            "--output={$this->tmpDir}",
        ]);
        $this->assertSame(0, $result['status'], $result['output']);

        // README has site name + domain
        $readme = file_get_contents("{$this->tmpDir}/README.md");
        $this->assertStringContainsString('Demo Site', $readme);
        $this->assertStringContainsString('demo.example', $readme);
        $this->assertStringNotContainsString('{{SITE_NAME}}', $readme);
        $this->assertStringNotContainsString('{{SITE_DOMAIN}}', $readme);

        // dependabot.yml has reviewer handles
        $dependabot = file_get_contents("{$this->tmpDir}/.github/dependabot.yml");
        $this->assertStringContainsString('alice', $dependabot);
        $this->assertStringContainsString('bob', $dependabot);
        $this->assertStringNotContainsString('{{REVIEWER_HANDLE}}', $dependabot);
        $this->assertStringNotContainsString('{{BACKSTOP_HANDLE}}', $dependabot);

        // deploy.yml has domain in smoke URL
        $deploy = file_get_contents("{$this->tmpDir}/.github/workflows/deploy.yml");
        $this->assertStringContainsString('https://www.demo.example/', $deploy);
        $this->assertStringNotContainsString('{{SITE_DOMAIN}}', $deploy);
    }

    public function test_leaves_no_unsubstituted_placeholders(): void
    {
        $result = $this->runCli([
            '--site=Demo',
            '--domain=demo.example',
            '--reviewer=alice',
            '--backstop=bob',
            "--output={$this->tmpDir}",
        ]);
        $this->assertSame(0, $result['status'], $result['output']);

        // Walk all generated files and assert no {{...}} placeholder remains
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iter as $file) {
            if ($file->isFile() && $file->getExtension() !== 'png' && $file->getExtension() !== 'jpg') {
                $content = file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    '/\{\{[A-Z_]+\}\}/',
                    $content,
                    "Found unsubstituted placeholder in " . $file->getPathname()
                );
            }
        }
    }

    public function test_refuses_to_overwrite_existing_non_empty_dir(): void
    {
        mkdir($this->tmpDir);
        file_put_contents("{$this->tmpDir}/existing.txt", 'leave me alone');

        $result = $this->runCli([
            '--site=demo',
            '--domain=demo.example',
            '--reviewer=alice',
            '--backstop=bob',
            "--output={$this->tmpDir}",
        ]);

        $this->assertNotSame(0, $result['status']);
        $this->assertStringContainsString('not empty', $result['output']);
        $this->assertFileExists("{$this->tmpDir}/existing.txt");
    }
}
