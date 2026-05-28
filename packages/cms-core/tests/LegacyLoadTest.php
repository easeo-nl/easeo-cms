<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;

class LegacyLoadTest extends TestCase {
    public function testLegacyFunctiesBeschikbaar(): void {
        ob_start();
        require_once __DIR__ . '/../src/legacy/bootstrap.php';
        ob_end_clean();

        $this->assertTrue(function_exists('brand_css_properties'), 'brand.php moet geladen zijn');
        $this->assertTrue(function_exists('send_mail'), 'mailer.php moet geladen zijn');
        $this->assertTrue(function_exists('get_forms'), 'form-engine.php moet geladen zijn');
    }
}
