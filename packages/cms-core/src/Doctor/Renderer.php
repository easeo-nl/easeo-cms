<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor;

final class Renderer
{
    private bool $useAnsi;

    public function __construct(?bool $useAnsi = null)
    {
        $this->useAnsi = $useAnsi ?? self::detectTty();
    }

    /**
     * @param list<CheckResult> $results
     */
    public function render(array $results): string
    {
        $out = '';
        foreach ($results as $r) {
            $icon = match ($r->status) {
                CheckStatus::Ok      => '[OK]',
                CheckStatus::Warn    => '[WARN]',
                CheckStatus::Error   => '[ERROR]',
                CheckStatus::Skipped => '[SKIP]',
            };
            $color = match ($r->status) {
                CheckStatus::Ok      => "\033[32m",
                CheckStatus::Warn    => "\033[33m",
                CheckStatus::Error   => "\033[31m",
                CheckStatus::Skipped => "\033[90m",
            };
            $reset = "\033[0m";

            $line = sprintf('%s %s', $icon, $r->label);
            if ($r->detail !== '') {
                $line .= ': ' . $r->detail;
            }
            if ($r->nextAction !== null) {
                $line .= "\n  -> " . $r->nextAction;
            }

            $out .= $this->useAnsi ? $color . $line . $reset : $line;
            $out .= "\n";
        }
        return $out;
    }

    private static function detectTty(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && stream_isatty(STDOUT);
    }
}
