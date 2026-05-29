<?php
declare(strict_types=1);

namespace Easeo\Cms\Mail;

final class Mailer
{
    /**
     * Send an email — auto-selects SMTP or native mail() based on env / site.json config.
     *
     * Resolution order (12-factor):
     *  1. Environment variables (SMTP_HOST, …) — set in .env or server config.
     *  2. Legacy site.json["smtp"] — backwards-compat for existing klant-sites.
     *  3. Native PHP mail() — ultimate fallback.
     *
     * @return true|string  true on success, error message on failure
     */
    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool|string
    {
        $siteData = json_decode((string) @file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
        $smtp = self::resolveSmtpConfig($siteData);

        if ($smtp !== null) {
            $result = self::sendSmtp($to, $subject, $body, $replyTo, $smtp, $siteData);
            if ($result === true) {
                return true;
            }
            error_log('SMTP failed, falling back to mail(): ' . $result);
        }

        return self::sendNative($to, $subject, $body, $replyTo, $siteData);
    }

    /**
     * Resolve SMTP config — env vars take precedence over legacy site.json.
     *
     * @param  array<string,mixed> $siteData
     * @return array{enabled:bool,host:string,port:int,username:string,password:string,from_email:string,from_name:string,encryption:string,source:string}|null
     *         null when no usable config exists.
     */
    private static function resolveSmtpConfig(array $siteData): ?array
    {
        // 1. Prefer environment (12-factor)
        $envHost = \Easeo\Cms\Config\Environment::get('SMTP_HOST');
        if ($envHost !== null && $envHost !== '') {
            $username = \Easeo\Cms\Config\Environment::get('SMTP_USERNAME', '');
            if ($username === '' || $username === null) {
                return null; // SMTP host without username — can't authenticate
            }
            return [
                'enabled'    => true,
                'host'       => $envHost,
                'port'       => \Easeo\Cms\Config\Environment::int('SMTP_PORT', 465),
                'username'   => $username,
                'password'   => \Easeo\Cms\Config\Environment::get('SMTP_PASSWORD', '') ?? '',
                'from_email' => \Easeo\Cms\Config\Environment::get('SMTP_FROM_EMAIL', $username) ?? $username,
                'from_name'  => \Easeo\Cms\Config\Environment::get('SMTP_FROM_NAME', $siteData['company']['name'] ?? 'Website') ?? ($siteData['company']['name'] ?? 'Website'),
                'encryption' => \Easeo\Cms\Config\Environment::get('SMTP_ENCRYPTION', 'ssl') ?? 'ssl',
                'source'     => 'env',
            ];
        }

        // 2. Fall back to legacy site.json
        $smtp = $siteData['smtp'] ?? [];
        if (empty($smtp['enabled']) || empty($smtp['host']) || empty($smtp['username'])) {
            return null;
        }
        return [
            'enabled'    => true,
            'host'       => (string) $smtp['host'],
            'port'       => (int) (($smtp['port'] ?? 0) ?: 465),
            'username'   => (string) $smtp['username'],
            // Legacy passwords are AES-256-CBC encrypted; decrypt them.
            'password'   => self::decryptSmtpPassword((string) ($smtp['password'] ?? '')),
            'from_email' => (string) (($smtp['from_email'] ?? '') ?: $smtp['username']),
            'from_name'  => (string) (($smtp['from_name'] ?? '') ?: ($siteData['company']['name'] ?? 'Website')),
            'encryption' => (string) ($smtp['encryption'] ?? 'ssl'),
            'source'     => 'site.json',
        ];
    }

    /**
     * Encrypt an SMTP password for storage in site.json.
     */
    public static function encryptSmtpPassword(string $password): string
    {
        $key = self::cryptoKey();
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Decrypt an SMTP password retrieved from site.json. Returns '' on any failure.
     */
    public static function decryptSmtpPassword(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }
        $key = self::cryptoKey();
        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            return '';
        }
        $parts = explode('::', $decoded, 2);
        if (count($parts) !== 2) {
            return '';
        }
        $result = openssl_decrypt($parts[1], 'AES-256-CBC', $key, 0, $parts[0]);
        return $result !== false ? $result : '';
    }

    /**
     * Derive the AES-256 key.
     *
     * Backwards-compat note: legacy/mailer.php used `__DIR__ . '/data/.htaccess'` as
     * the hash-input string. In that file, __DIR__ resolved to
     * "packages/cms-core/src/legacy", so the hashed string was literally
     * "packages/cms-core/src/legacy/data/.htaccess" (a non-existent path — hash()
     * doesn't care). We MUST reproduce that exact string here; otherwise all existing
     * klant-sites' encrypted SMTP passwords would fail to decrypt after the upgrade.
     *
     * From Mail/Mailer.php: dirname(__DIR__, 2) is "packages/cms-core" (two levels up
     * from Mail/), so appending '/src/legacy/data/.htaccess' reconstructs the original
     * string. This intentionally references the now-deleted legacy path — do not change.
     */
    private static function cryptoKey(): string
    {
        $legacyAnchor = dirname(__DIR__, 2) . '/src/legacy/data/.htaccess';
        return hash('sha256', $legacyAnchor, true);
    }

    /**
     * Send via SMTP using PHPMailer.
     *
     * PHPMailer lives in vendor-legacy/phpmailer/ (vendored library, intentionally not
     * PSR-4 migrated). Required via relative path from the package root.
     *
     * @param array{enabled:bool,host:string,port:int,username:string,password:string,from_email:string,from_name:string,encryption:string,source:string} $smtp  Pre-resolved config from resolveSmtpConfig()
     * @param array<string,mixed> $siteData
     * @return true|string  true on success, error message on failure
     */
    private static function sendSmtp(string $to, string $subject, string $body, string $replyTo, array $smtp, array $siteData): bool|string
    {
        require_once dirname(__DIR__, 2) . '/vendor-legacy/phpmailer/load.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host     = $smtp['host'];
            $mail->Port     = $smtp['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password']; // already plaintext (env) or decrypted (site.json)

            $encryption = $smtp['encryption'];
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($to);

            if ($replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));

            $mail->send();
            return true;
        } catch (\Exception $e) {
            return $mail->ErrorInfo;
        }
    }

    /**
     * Send via native PHP mail().
     *
     * @param array<string,mixed> $siteData
     * @return true|string  true on success, error message on failure
     */
    private static function sendNative(string $to, string $subject, string $body, string $replyTo, array $siteData): bool|string
    {
        $fromName = $siteData['smtp']['from_name'] ?? $siteData['company']['name'] ?? 'Website';
        $fromEmail = $siteData['smtp']['from_email'] ?? $siteData['company']['email'] ?? ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        if ($replyTo !== '') {
            $headers .= "Reply-To: {$replyTo}\r\n";
        }

        $sent = @mail($to, $subject, $body, $headers);
        return $sent ? true : 'PHP mail() heeft de e-mail niet kunnen versturen.';
    }
}
