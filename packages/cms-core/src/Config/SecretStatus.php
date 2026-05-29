<?php
declare(strict_types=1);

namespace Easeo\Cms\Config;

/**
 * Read-only status checker for environment-backed secrets. Used by the
 * beheer-UI "Geheimen" page to show whether deployment-side secrets are
 * configured, without exposing the values themselves.
 */
final class SecretStatus
{
    /** @var array<string,array{label:string,hint:string,required:bool}> */
    private const KNOWN_SECRETS = [
        'SMTP_HOST'         => ['label' => 'SMTP host',          'hint' => 'smtp.example.com',       'required' => false],
        'SMTP_PORT'         => ['label' => 'SMTP poort',         'hint' => '465 of 587',             'required' => false],
        'SMTP_USERNAME'     => ['label' => 'SMTP gebruikersnaam','hint' => 'noreply@klantsite.nl',   'required' => false],
        'SMTP_PASSWORD'     => ['label' => 'SMTP wachtwoord',    'hint' => 'set via .env',           'required' => false],
        'SMTP_FROM_EMAIL'   => ['label' => 'Afzender-email',     'hint' => 'noreply@klantsite.nl',   'required' => false],
        'SMTP_FROM_NAME'    => ['label' => 'Afzender-naam',      'hint' => 'Klant Naam',             'required' => false],
        'SMTP_ENCRYPTION'   => ['label' => 'Encryptie',          'hint' => 'ssl, tls of leeg',       'required' => false],
        'MOLLIE_API_KEY'    => ['label' => 'Mollie API key',     'hint' => 'live_xxx of test_xxx',   'required' => false],
        'GTM_ID'            => ['label' => 'GTM container ID',   'hint' => 'GTM-XXXXXXX',            'required' => false],
    ];

    public static function isConfigured(string $key): bool
    {
        return Environment::has($key);
    }

    /**
     * @return list<array{key:string,label:string,hint:string,required:bool,configured:bool}>
     */
    public static function summary(): array
    {
        $rows = [];
        foreach (self::KNOWN_SECRETS as $key => $meta) {
            $rows[] = [
                'key'        => $key,
                'label'      => $meta['label'],
                'hint'       => $meta['hint'],
                'required'   => $meta['required'],
                'configured' => self::isConfigured($key),
            ];
        }
        return $rows;
    }

    /**
     * @return list<string> Keys flagged required but not configured.
     */
    public static function missingRequired(): array
    {
        $missing = [];
        foreach (self::KNOWN_SECRETS as $key => $meta) {
            if ($meta['required'] && !self::isConfigured($key)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }
}
