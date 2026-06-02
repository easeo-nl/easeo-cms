<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor;

enum CheckStatus: string
{
    case Ok      = 'ok';
    case Warn    = 'warn';
    case Error   = 'error';
    case Skipped = 'skipped';
}
