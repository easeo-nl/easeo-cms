<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor;

interface CheckInterface
{
    public function label(): string;
    public function run(): CheckResult;
}
