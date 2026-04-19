<?php
namespace Easeo\Cms\Tests;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase {
    public function testAutoloaderWerkt(): void {
        $this->assertTrue(class_exists('PHPUnit\\Framework\\TestCase'));
    }
}
