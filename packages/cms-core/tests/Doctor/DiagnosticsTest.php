<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Doctor;

use Easeo\Cms\Doctor\{CheckInterface, CheckResult, CheckStatus, Diagnostics};
use PHPUnit\Framework\TestCase;

final class DiagnosticsTest extends TestCase
{
    public function testEmptyDiagnosticsReturnsEmptyResults(): void
    {
        $d = new Diagnostics();
        $this->assertSame([], $d->run());
    }

    public function testCheckThatThrowsIsCaughtAndReportedAsError(): void
    {
        $check = new class implements CheckInterface {
            public function label(): string { return 'Boom check'; }
            public function run(): CheckResult
            {
                throw new \RuntimeException('Something exploded');
            }
        };

        $d       = new Diagnostics();
        $results = $d->add($check)->run();

        $this->assertCount(1, $results);
        $this->assertSame(CheckStatus::Error, $results[0]->status);
        $this->assertSame('Boom check', $results[0]->label);
        $this->assertStringContainsString('Something exploded', $results[0]->detail);
    }

    public function testOrderingIsPreserved(): void
    {
        $makeCheck = fn(string $label, CheckStatus $status) =>
            new class($label, $status) implements CheckInterface {
                public function __construct(
                    private readonly string $l,
                    private readonly CheckStatus $s,
                ) {}
                public function label(): string { return $this->l; }
                public function run(): CheckResult
                {
                    return new CheckResult($this->s, $this->l);
                }
            };

        $d = new Diagnostics();
        $d->add($makeCheck('Alpha', CheckStatus::Ok))
          ->add($makeCheck('Beta',  CheckStatus::Warn))
          ->add($makeCheck('Gamma', CheckStatus::Error));

        $results = $d->run();
        $this->assertCount(3, $results);
        $this->assertSame('Alpha', $results[0]->label);
        $this->assertSame('Beta',  $results[1]->label);
        $this->assertSame('Gamma', $results[2]->label);
    }
}
