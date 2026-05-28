<?php
declare(strict_types=1);

namespace Easeo\Cms\Mail;

final class Mailer
{
    /**
     * Send an email — auto-selects SMTP or native mail() based on site.json config.
     *
     * @return true|string  true on success, error message on failure
     */
    public static function send(string $to, string $subject, string $body, string $replyTo = ''): bool|string
    {
        $siteData = json_decode((string) file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
        $smtp = $siteData['smtp'] ?? [];

        if (!empty($smtp['enabled']) && !empty($smtp['host']) && !empty($smtp['username'])) {
            $result = self::sendSmtp($to, $subject, $body, $replyTo, $smtp, $siteData);
            if ($result === true) {
                return true;
            }
            error_log('SMTP failed, falling back to mail(): ' . $result);
        }

        return self::sendNative($to, $subject, $body, $replyTo, $siteData);
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
     * PHPMailer remains in legacy/phpmailer/ (vendored library, intentionally not
     * PSR-4 migrated). Required via relative path from src/Mail/ to src/legacy/.
     *
     * @param array<string,mixed> $smtp
     * @param array<string,mixed> $siteData
     * @return true|string  true on success, error message on failure
     */
    private static function sendSmtp(string $to, string $subject, string $body, string $replyTo, array $smtp, array $siteData): bool|string
    {
        require_once dirname(__DIR__) . '/legacy/phpmailer/load.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->Port = (int) ($smtp['port'] ?: 465);
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = self::decryptSmtpPassword($smtp['password'] ?? '');

            $encryption = $smtp['encryption'] ?? 'ssl';
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            }

            $fromEmail = $smtp['from_email'] ?: $smtp['username'];
            $fromName = $smtp['from_name'] ?: ($siteData['company']['name'] ?? 'Website');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if ($replyTo !== '') {
                $mail->addReplyTo($replyTo);
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;
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
