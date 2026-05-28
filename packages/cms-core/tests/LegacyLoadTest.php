<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;
use Easeo\Cms\Constants;

class LegacyLoadTest extends TestCase {
    public function testPsr4KlassenBeschikbaar(): void {
        $this->assertTrue(class_exists(\Easeo\Cms\Branding\BrandConfig::class), 'BrandConfig PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Mail\Mailer::class), 'Mailer PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Form\FormEngine::class), 'FormEngine PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Blog\BlogEngine::class), 'BlogEngine PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Media\MediaLibrary::class), 'MediaLibrary PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Navigation\Menu::class), 'Menu PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Seo\StructuredData::class), 'StructuredData PSR-4 klasse moet beschikbaar zijn');
        $this->assertTrue(class_exists(\Easeo\Cms\Security\RateLimiter::class), 'RateLimiter PSR-4 klasse moet beschikbaar zijn');
    }

    public function testConstantsBootstrapDefinesConstants(): void {
        // Constants are already defined by tests/bootstrap.php; just verify they exist.
        $this->assertTrue(defined('EASEO_APP'), 'EASEO_APP moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_DATA'), 'EASEO_DATA moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_CORE'), 'EASEO_CORE moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_TEMPLATES'), 'EASEO_TEMPLATES moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_LANG'), 'EASEO_LANG moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_BEHEER'), 'EASEO_BEHEER moet gedefinieerd zijn na Constants::bootstrap()');
        $this->assertTrue(defined('EASEO_ROOT'), 'EASEO_ROOT moet gedefinieerd zijn na Constants::bootstrap()');
    }

    public function testConstantsCorePointsToCmsCore(): void {
        $this->assertStringEndsWith('/packages/cms-core', EASEO_CORE);
        $this->assertDirectoryExists(EASEO_CORE);
    }
}
