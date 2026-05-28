<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;

class LegacyLoadTest extends TestCase {
    public function testLegacyFunctiesBeschikbaar(): void {
        ob_start();
        require_once __DIR__ . '/../src/legacy/bootstrap.php';
        ob_end_clean();

        $this->assertTrue(class_exists(\Easeo\Cms\Branding\BrandConfig::class), 'BrandConfig PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Mail\Mailer::class), 'Mailer PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Form\FormEngine::class), 'FormEngine PSR-4 klasse moet beschikbaar zijn');
    }
}
