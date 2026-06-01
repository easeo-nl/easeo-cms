<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};
use Easeo\Cms\Migration\SchemaVersion;

final class SchemaVersionCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return 'Schema version';
    }

    public function run(): CheckResult
    {
        $dataDir = $this->appRoot . '/data';
        if (!is_dir($dataDir)) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'data/ does not exist (run bin/easeo-bootstrap?)',
                'Run: ./bin/easeo-bootstrap',
            );
        }
        $version = (new SchemaVersion($dataDir))->current();
        return new CheckResult(
            CheckStatus::Ok,
            $this->label(),
            "v$version",
        );
    }
}
