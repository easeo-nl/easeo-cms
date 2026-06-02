<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor\Checks;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus};

final class SiteHealthCheck implements CheckInterface
{
    public function __construct(private readonly string $appRoot) {}

    public function label(): string
    {
        return 'Site HTTP probe';
    }

    public function run(): CheckResult
    {
        $url = $this->resolveSiteUrl();
        if ($url === null) {
            return new CheckResult(
                CheckStatus::Skipped,
                $this->label(),
                'Geen site_url geconfigureerd in site.config.json',
            );
        }

        // Use stream context with short timeout
        $context = stream_context_create([
            'http' => [
                'timeout'          => 5,
                'follow_location'  => true,
                'max_redirects'    => 3,
                'method'           => 'GET',
                'ignore_errors'    => true,
            ],
            'ssl'  => ['verify_peer' => false],
        ]);

        $start    = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $elapsed  = round((microtime(true) - $start) * 1000);

        if ($response === false) {
            return new CheckResult(
                CheckStatus::Error,
                $this->label(),
                "Kon $url niet bereiken",
                'Controleer DNS, webserver, en firewall',
            );
        }

        // Parse HTTP status from $http_response_header
        $status = $this->parseHttpStatus($http_response_header ?? []);
        if ($status >= 200 && $status < 400) {
            return new CheckResult(
                CheckStatus::Ok,
                $this->label(),
                "HTTP $status in {$elapsed}ms — $url",
            );
        }

        return new CheckResult(
            CheckStatus::Warn,
            $this->label(),
            "HTTP $status in {$elapsed}ms — $url",
            'Onverwachte HTTP-status — controleer de webserver-logs',
        );
    }

    private function resolveSiteUrl(): ?string
    {
        $configFile = $this->appRoot . '/site.config.json';
        if (!is_file($configFile)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($configFile), true);
        $url  = $data['site_url'] ?? null;
        return is_string($url) && $url !== '' ? rtrim($url, '/') . '/' : null;
    }

    /** @param list<string> $headers */
    private function parseHttpStatus(array $headers): int
    {
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }
}
