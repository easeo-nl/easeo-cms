<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Mail;

use Easeo\Cms\Mail\Mailer;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Existing encrypt/decrypt tests
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // resolveSmtpConfig() — accessed via ReflectionMethod for test isolation
    // -------------------------------------------------------------------------

    /** @var array<string,string|false> */
    private array $savedEnv = [];

    private const SMTP_KEYS = [
        'SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD',
        'SMTP_FROM_EMAIL', 'SMTP_FROM_NAME', 'SMTP_ENCRYPTION',
    ];

    protected function setUp(): void
    {
        $this->savedEnv = [];
        foreach (self::SMTP_KEYS as $k) {
            $v = getenv($k);
            $this->savedEnv[$k] = $v;
            putenv($k); // unset to ensure clean baseline
            unset($_ENV[$k], $_SERVER[$k]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $k => $v) {
            if ($v === false) {
                putenv($k);
                unset($_ENV[$k], $_SERVER[$k]);
            } else {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }

    /**
     * Invoke the private static resolveSmtpConfig() via Reflection.
     *
     * @param  array<string,mixed> $siteData
     * @return array<string,mixed>|null
     */
    private function resolveConfigViaReflection(array $siteData): ?array
    {
        $method = new \ReflectionMethod(Mailer::class, 'resolveSmtpConfig');
        $method->setAccessible(true);
        return $method->invoke(null, $siteData);
    }

    public function test_resolve_returns_null_when_no_env_and_no_site_json_smtp(): void
    {
        $result = $this->resolveConfigViaReflection([]);
        $this->assertNull($result);
    }

    public function test_resolve_returns_null_when_site_json_has_smtp_disabled(): void
    {
        $siteData = [
            'smtp' => [
                'enabled'  => false,
                'host'     => 'smtp.example.com',
                'username' => 'user@example.com',
            ],
        ];
        $result = $this->resolveConfigViaReflection($siteData);
        $this->assertNull($result);
    }

    public function test_resolve_uses_env_when_smtp_host_set(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=env-user@example.com');
        putenv('SMTP_PASSWORD=s3cr3t');

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame('smtp.example.com', $result['host']);
        $this->assertSame('env-user@example.com', $result['username']);
        $this->assertSame('s3cr3t', $result['password']);
        $this->assertSame('env', $result['source']);
        $this->assertTrue($result['enabled']);
    }

    public function test_resolve_env_wins_over_site_json_when_both_set(): void
    {
        putenv('SMTP_HOST=env-smtp.example.com');
        putenv('SMTP_USERNAME=env-user@example.com');

        $siteData = [
            'smtp' => [
                'enabled'  => true,
                'host'     => 'legacy-smtp.example.com',
                'username' => 'legacy-user@example.com',
            ],
        ];

        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame('env-smtp.example.com', $result['host']);
        $this->assertSame('env', $result['source']);
    }

    public function test_resolve_requires_smtp_username_alongside_host(): void
    {
        // SMTP_HOST set but SMTP_USERNAME missing — should return null
        putenv('SMTP_HOST=smtp.example.com');
        // SMTP_USERNAME intentionally not set

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNull($result);
    }

    public function test_resolve_default_port_465_when_smtp_port_unset(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        // SMTP_PORT intentionally not set

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame(465, $result['port']);
    }

    public function test_resolve_uses_explicit_port_from_env(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        putenv('SMTP_PORT=587');

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame(587, $result['port']);
    }

    public function test_resolve_default_encryption_ssl_when_unset(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        // SMTP_ENCRYPTION intentionally not set

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame('ssl', $result['encryption']);
    }

    public function test_resolve_from_email_defaults_to_username_when_unset(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        // SMTP_FROM_EMAIL intentionally not set

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame('user@example.com', $result['from_email']);
    }

    public function test_resolve_from_name_defaults_to_company_name_when_unset(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        // SMTP_FROM_NAME intentionally not set

        $siteData = ['company' => ['name' => 'Acme BV']];
        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame('Acme BV', $result['from_name']);
    }

    public function test_resolve_from_name_falls_back_to_website_when_no_company(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');
        // SMTP_FROM_NAME and company name both absent

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame('Website', $result['from_name']);
    }

    public function test_resolve_falls_back_to_legacy_site_json_smtp_when_env_unset(): void
    {
        // No env vars set — relies on site.json only
        $encryptedPassword = Mailer::encryptSmtpPassword('legacy-pass');

        $siteData = [
            'smtp' => [
                'enabled'    => true,
                'host'       => 'legacy.smtp.nl',
                'port'       => 587,
                'username'   => 'legacy@example.nl',
                'password'   => $encryptedPassword,
                'from_email' => 'from@example.nl',
                'from_name'  => 'Legacy Site',
                'encryption' => 'tls',
            ],
        ];

        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame('site.json', $result['source']);
        $this->assertSame('legacy.smtp.nl', $result['host']);
        $this->assertSame(587, $result['port']);
        $this->assertSame('legacy@example.nl', $result['username']);
        $this->assertSame('legacy-pass', $result['password'], 'Legacy password must be decrypted from site.json');
        $this->assertSame('from@example.nl', $result['from_email']);
        $this->assertSame('Legacy Site', $result['from_name']);
        $this->assertSame('tls', $result['encryption']);
        $this->assertTrue($result['enabled']);
    }

    public function test_resolve_marks_source_env_correctly(): void
    {
        putenv('SMTP_HOST=smtp.example.com');
        putenv('SMTP_USERNAME=user@example.com');

        $result = $this->resolveConfigViaReflection([]);

        $this->assertNotNull($result);
        $this->assertSame('env', $result['source']);
    }

    public function test_resolve_marks_source_site_json_correctly(): void
    {
        // No env vars — uses site.json path
        $siteData = [
            'smtp' => [
                'enabled'  => true,
                'host'     => 'smtp.example.com',
                'username' => 'user@example.com',
            ],
        ];

        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame('site.json', $result['source']);
    }

    public function test_resolve_site_json_missing_port_defaults_to_465(): void
    {
        $siteData = [
            'smtp' => [
                'enabled'  => true,
                'host'     => 'smtp.example.com',
                'port'     => 0, // falsy — should default to 465
                'username' => 'user@example.com',
            ],
        ];

        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame(465, $result['port']);
    }

    public function test_resolve_site_json_from_email_falls_back_to_username(): void
    {
        $siteData = [
            'smtp' => [
                'enabled'    => true,
                'host'       => 'smtp.example.com',
                'username'   => 'user@example.com',
                'from_email' => '', // empty — should use username
            ],
        ];

        $result = $this->resolveConfigViaReflection($siteData);

        $this->assertNotNull($result);
        $this->assertSame('user@example.com', $result['from_email']);
    }
}
