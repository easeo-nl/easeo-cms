<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Mail;

use Easeo\Cms\Mail\Mailer;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    public function test_encrypt_then_decrypt_round_trip(): void
    {
        $original = 'super-secret-smtp-password-123!@#';
        $encrypted = Mailer::encryptSmtpPassword($original);
        $this->assertNotSame($original, $encrypted, 'encrypted value should differ from input');
        $this->assertNotEmpty($encrypted);

        $decrypted = Mailer::decryptSmtpPassword($encrypted);
        $this->assertSame($original, $decrypted);
    }

    public function test_decrypt_returns_empty_for_empty_input(): void
    {
        $this->assertSame('', Mailer::decryptSmtpPassword(''));
    }

    public function test_decrypt_returns_empty_for_invalid_base64(): void
    {
        $this->assertSame('', Mailer::decryptSmtpPassword('!!!not-valid-base64!!!'));
    }

    public function test_decrypt_returns_empty_for_malformed_encrypted_string(): void
    {
        // Base64-encoded but missing the '::' separator
        $bad = base64_encode('no-separator-here');
        $this->assertSame('', Mailer::decryptSmtpPassword($bad));
    }

    public function test_encrypt_produces_different_output_each_call(): void
    {
        // openssl_random_pseudo_bytes for IV ensures non-deterministic ciphertext
        $a = Mailer::encryptSmtpPassword('same-password');
        $b = Mailer::encryptSmtpPassword('same-password');
        $this->assertNotSame($a, $b, 'random IV should produce different ciphertext each time');
    }

    public function test_decrypt_returns_empty_for_wrong_key_or_tampered_data(): void
    {
        // Tamper the encrypted payload after the IV separator
        $encrypted = Mailer::encryptSmtpPassword('hello');
        $decoded = base64_decode($encrypted);
        [$iv, $cipher] = explode('::', $decoded, 2);
        // Flip one byte in the cipher
        $cipher[0] = $cipher[0] === 'A' ? 'B' : 'A';
        $tampered = base64_encode($iv . '::' . $cipher);
        $this->assertSame('', Mailer::decryptSmtpPassword($tampered));
    }
}
