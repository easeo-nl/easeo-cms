<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};

/**
 * Optionele check: vraag via `gh` CLI hoeveel open Dependabot-PRs er zijn.
 * Wordt gracefully overgeslagen als `gh` niet geinstalleerd is.
 */
final class DependabotPrsCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return 'Open Dependabot PRs';
    }

    public function run(): CheckResult
    {
        // Check if gh CLI is available
        exec('which gh 2>/dev/null', $out, $code);
        if ($code !== 0) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'gh CLI niet gevonden (installeer GitHub CLI voor deze check)',
            );
        }

        // Try to determine repo from git remote
        $repoDir = $this->appRoot;
        $remoteOutput = [];
        exec("git -C " . escapeshellarg($repoDir) . " remote get-url origin 2>/dev/null", $remoteOutput, $gitCode);
        if ($gitCode !== 0 || empty($remoteOutput)) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'Geen git remote gevonden',
            );
        }

        $remote = trim($remoteOutput[0]);
        // Parse owner/repo from SSH or HTTPS remote URL
        if (preg_match('#github\.com[:/](.+?/.+?)(?:\.git)?$#', $remote, $m)) {
            $repo = $m[1];
        } else {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'Geen GitHub remote herkend',
            );
        }

        $prListOutput = [];
        exec(
            "gh pr list --repo " . escapeshellarg($repo) . " --label dependabot --state open --json number --jq 'length' 2>/dev/null",
            $prListOutput,
            $exitCode
        );

        if ($exitCode !== 0) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'gh pr list mislukt (mogelijk niet ingelogd: run `gh auth login`)',
            );
        }

        $count = (int) trim(implode('', $prListOutput));
        if ($count === 0) {
            return new CheckResult(
                CheckStatus::Ok,
                $this->label(),
                'Geen open Dependabot PRs',
            );
        }

        return new CheckResult(
            CheckStatus::Warn,
            $this->label(),
            "$count open Dependabot PR(s)",
            "Merge via: gh pr list --label dependabot --state open",
        );
    }
}
