<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor;

final readonly class CheckResult
{
    public function __construct(
        public CheckStatus $status,
        public string $label,
        public string $detail = '',
        public ?string $nextAction = null,
    ) {}
}
