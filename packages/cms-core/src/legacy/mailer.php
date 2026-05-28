<?php
/**
 * EASEO CMS — Central mail sender (SMTP with PHPMailer or native mail() fallback)
 */

/**
 * Encrypt SMTP password for storage in site.json
 */
function encrypt_smtp_password(string $password): string {
    $key = hash('sha256', __DIR__ . '/data/.htaccess', true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

/**
 * Decrypt SMTP password from site.json
 */
function decrypt_smtp_password(string $encrypted): string {
    if (empty($encrypted)) return '';
    $key = hash('sha256', __DIR__ . '/data/.htaccess', true);
    $decoded = base64_decode($encrypted);
    if ($decoded === false) return '';
    $parts = explode('::', $decoded, 2);
    if (count($parts) !== 2) return '';
    $result = openssl_decrypt($parts[1], 'AES-256-CBC', $key, 0, $parts[0]);
    return $result !== false ? $result : '';
}

/**
 * Send an email — automatically chooses SMTP or native mail()
 *
 * @return true on success, string error message on failure
 */
function send_mail(string $to, string $subject, string $body, string $reply_to = '') {
    $siteData = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true) ?: [];
    $smtp = $siteData['smtp'] ?? [];

    if (!empty($smtp['enabled']) && !empty($smtp['host']) && !empty($smtp['username'])) {
        $result = send_mail_smtp($to, $subject, $body, $reply_to, $smtp, $siteData);
        if ($result === true) return true;
        // Fallback to native mail on SMTP failure
        error_log('SMTP failed, falling back to mail(): ' . $result);
    }

    return send_mail_native($to, $subject, $body, $reply_to, $siteData);
}

/**
 * Send via SMTP using PHPMailer
 *
 * @return true on success, string error message on failure
 */
function send_mail_smtp(string $to, string $subject, string $body, string $reply_to, array $smtp, array $siteData) {
    require_once __DIR__ . '/phpmailer/load.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = (int)($smtp['port'] ?: 465);
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = decrypt_smtp_password($smtp['password'] ?? '');

        $encryption = $smtp['encryption'] ?? 'ssl';
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromEmail = $smtp['from_email'] ?: $smtp['username'];
        $fromName = $smtp['from_name'] ?: ($siteData['company']['name'] ?? 'Website');
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        if ($reply_to) {
            $mail->addReplyTo($reply_to);
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
 * Send via native PHP mail()
 *
 * @return true on success, string error message on failure
 */
function send_mail_native(string $to, string $subject, string $body, string $reply_to, array $siteData) {
    $fromName = $siteData['smtp']['from_name'] ?? $siteData['company']['name'] ?? 'Website';
    $fromEmail = $siteData['smtp']['from_email'] ?? $siteData['company']['email'] ?? ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    if ($reply_to) {
        $headers .= "Reply-To: {$reply_to}\r\n";
    }

    $sent = @mail($to, $subject, $body, $headers);
    return $sent ? true : 'PHP mail() heeft de e-mail niet kunnen versturen.';
}
