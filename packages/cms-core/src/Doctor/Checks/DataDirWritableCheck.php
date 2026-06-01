<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};

final class DataDirWritableCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return 'EASEO_DATA writable';
    }

    public function run(): CheckResult
    {
        $dataDir = $this->appRoot . '/data';
        if (!is_dir($dataDir)) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'data/ does not exist',
                'Run: ./bin/easeo-bootstrap',
            );
        }
        if (!is_writable($dataDir)) {
            return new CheckResult(
                CheckStatus::Error,
                $this->label(),
                'data/ directory is not writable',
                'Run: chmod 755 data/',
            );
        }
        return new CheckResult(
            CheckStatus::Ok,
            $this->label(),
            $dataDir,
        );
    }
}
