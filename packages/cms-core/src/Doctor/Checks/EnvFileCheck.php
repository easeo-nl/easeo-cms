<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};

final class EnvFileCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return '.env presence';
    }

    public function run(): CheckResult
    {
        $envFile   = $this->appRoot . '/.env';
        $localFile = $this->appRoot . '/.env.local';

        if (is_file($localFile)) {
            return new CheckResult(
                CheckStatus::Ok,
                $this->label(),
                '.env.local aanwezig',
            );
        }
        if (is_file($envFile)) {
            return new CheckResult(
                CheckStatus::Ok,
                $this->label(),
                '.env aanwezig',
            );
        }
        return new CheckResult(
            CheckStatus::Warn,
            $this->label(),
            'Geen .env gevonden — site gebruikt systeem-omgevingsvariabelen of defaults',
            'Kopieer .env.example naar .env en vul de waarden in',
        );
    }
}
