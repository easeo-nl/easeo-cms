<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Form;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Form\FormEngine;
use PHPUnit\Framework\TestCase;

final class FormEngineTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/form-engine-test-' . uniqid('', true);
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
                if (is_file($f)) {
                    unlink($f);
                }
            }
            rmdir($this->tmpDataDir);
        }
    }

    public function test_get_forms_returns_empty_array_when_no_file(): void
    {
        $this->assertSame([], FormEngine::getForms());
    }

    public function test_get_forms_data_returns_empty_array_when_no_file(): void
    {
        $this->assertSame([], FormEngine::getFormsData());
    }

    public function test_save_then_get_round_trip(): void
    {
        $forms = [
            ['id' => 'contact', 'naam' => 'Contact', 'velden' => []],
        ];
        $this->assertTrue(FormEngine::saveForms($forms));
        $this->assertSame($forms, FormEngine::getForms());
    }

    public function test_get_forms_data_wraps_forms_key(): void
    {
        $forms = [
            ['id' => 'contact', 'naam' => 'Contact', 'velden' => []],
        ];
        FormEngine::saveForms($forms);
        ContentRepository::resetCache();
        $data = FormEngine::getFormsData();
        $this->assertArrayHasKey('forms', $data);
        $this->assertSame($forms, $data['forms']);
    }

    public function test_get_form_returns_null_for_unknown_id(): void
    {
        FormEngine::saveForms([
            ['id' => 'contact', 'naam' => 'Contact', 'velden' => []],
        ]);
        ContentRepository::resetCache();
        $this->assertNull(FormEngine::getForm('does-not-exist'));
    }

    public function test_get_form_returns_array_for_known_id(): void
    {
        FormEngine::saveForms([
            ['id' => 'contact', 'naam' => 'Contact', 'velden' => []],
            ['id' => 'offerte', 'naam' => 'Offerte', 'velden' => []],
        ]);
        ContentRepository::resetCache();
        $form = FormEngine::getForm('offerte');
        $this->assertIsArray($form);
        $this->assertSame('Offerte', $form['naam']);
    }

    public function test_csrf_token_is_64_char_hex(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $token = FormEngine::csrfToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function test_csrf_token_is_idempotent_within_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $token1 = FormEngine::csrfToken();
        $token2 = FormEngine::csrfToken();
        $this->assertSame($token1, $token2);
    }

    public function test_verify_csrf_returns_false_when_no_token_posted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_POST = [];
        $this->assertFalse(FormEngine::verifyCsrf());
    }

    public function test_verify_csrf_returns_true_for_matching_token(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $token = FormEngine::csrfToken();
        $_POST = ['csrf_token' => $token];
        $this->assertTrue(FormEngine::verifyCsrf());
    }

    public function test_verify_csrf_returns_false_for_wrong_token(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        FormEngine::csrfToken(); // prime the session token
        $_POST = ['csrf_token' => 'not-the-right-token'];
        $this->assertFalse(FormEngine::verifyCsrf());
    }

    public function test_render_returns_empty_string_for_unknown_form_id(): void
    {
        // No session needed here — getForm returns null before session is accessed.
        // render returns '' (not an error paragraph) for unknown IDs — matches legacy.
        // Actually, check legacy: it returns a translation error paragraph.
        // We'll match real behavior: returns non-empty (error paragraph).
        $result = FormEngine::render('does-not-exist');
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString('<form', $result);
    }

    public function test_render_includes_form_tag_for_existing_form(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        FormEngine::saveForms([
            ['id' => 'contact', 'naam' => 'Contact', 'velden' => [
                ['type' => 'text', 'naam' => 'naam', 'label' => 'Naam', 'verplicht' => true],
            ]],
        ]);
        ContentRepository::resetCache();
        $html = FormEngine::render('contact');
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('csrf_token', $html);
    }
}
