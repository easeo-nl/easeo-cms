<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Doctor;

use Easeo\Cms\Doctor\{CheckResult, CheckStatus, Renderer};
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
{
    public function testRendersOkWarnErrorIcons(): void
    {
        $renderer = new Renderer(false);

        $results = [
            new CheckResult(CheckStatus::Ok,    'Version check', '1.2.3'),
            new CheckResult(CheckStatus::Warn,  'Env file',      'missing'),
            new CheckResult(CheckStatus::Error, 'Data dir',      'not writable'),
        ];

        $output = $renderer->render($results);

        $this->assertStringContainsString('[OK]',    $output);
        $this->assertStringContainsString('[WARN]',  $output);
        $this->assertStringContainsString('[ERROR]', $output);
    }

    public function testNoAnsiModeProducesNoEscapeCodes(): void
    {
        $renderer = new Renderer(false);
        $results  = [new CheckResult(CheckStatus::Ok, 'Test', 'detail')];
        $output   = $renderer->render($results);

        $this->assertStringNotContainsString("\033[", $output);
    }

    public function testNextActionAppearsAsArrowLine(): void
    {
        $renderer = new Renderer(false);
        $results  = [
            new CheckResult(
                CheckStatus::Error,
                'Data dir',
                'not writable',
                'Run: chmod 755 data/',
            ),
        ];

        $output = $renderer->render($results);
        $this->assertStringContainsString('-> Run: chmod 755 data/', $output);
    }
}
