<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Legal;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Legal\LegalPages;
use PHPUnit\Framework\TestCase;

final class LegalPagesTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/legal-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
        ContentRepository::resetCache();
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        ContentRepository::resetCache();
        if (is_dir($this->tmpDataDir)) {
            foreach (glob($this->tmpDataDir . '/*') ?: [] as $f) {
                if (is_file($f)) unlink($f);
            }
            rmdir($this->tmpDataDir);
        }
    }

    public function test_get_default_privacy_contains_dutch_header(): void
    {
        $text = LegalPages::getDefault('privacy');
        $this->assertStringContainsString('Privacyverklaring', $text);
        $this->assertStringContainsString('{bedrijfsnaam}', $text);
    }

    public function test_get_default_voorwaarden_contains_dutch_header(): void
    {
        $text = LegalPages::getDefault('voorwaarden');
        $this->assertStringContainsString('Algemene Voorwaarden', $text);
    }

    public function test_get_default_cookies_contains_dutch_header(): void
    {
        $text = LegalPages::getDefault('cookies');
        $this->assertStringContainsString('Cookiebeleid', $text);
    }

    public function test_get_default_unknown_type_returns_empty(): void
    {
        $this->assertSame('', LegalPages::getDefault('does-not-exist'));
    }

    public function test_replace_placeholders_substitutes_company_name(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/site.json",
            json_encode(['company' => ['name' => 'Acme BV']])
        );
        $result = LegalPages::replacePlaceholders('Hello {bedrijfsnaam}!');
        $this->assertSame('Hello Acme BV!', $result);
    }

    public function test_replace_placeholders_uses_placeholder_default_when_not_set(): void
    {
        // No site.json
        $result = LegalPages::replacePlaceholders('Contact: {email}');
        $this->assertStringContainsString('[E-mailadres]', $result);
    }

    public function test_replace_placeholders_substitutes_datum_with_current_date(): void
    {
        $result = LegalPages::replacePlaceholders('Date: {datum}');
        $today = date('d-m-Y');
        $this->assertSame("Date: $today", $result);
    }

    public function test_get_text_returns_default_when_legal_json_empty(): void
    {
        $text = LegalPages::getText('privacy');
        $this->assertStringContainsString('Privacyverklaring', $text);
        // {bedrijfsnaam} should be replaced (not present anymore)
        $this->assertStringNotContainsString('{bedrijfsnaam}', $text);
    }

    public function test_get_text_uses_legal_json_when_present(): void
    {
        file_put_contents(
            "{$this->tmpDataDir}/legal.json",
            json_encode(['privacy' => ['content' => 'Mijn eigen privacy tekst voor {bedrijfsnaam}.']])
        );
        file_put_contents(
            "{$this->tmpDataDir}/site.json",
            json_encode(['company' => ['name' => 'TestCo']])
        );
        $text = LegalPages::getText('privacy');
        $this->assertSame('Mijn eigen privacy tekst voor TestCo.', $text);
    }
}
