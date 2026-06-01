<?php
declare(strict_types=1);

namespace Easeo\Cms\Doctor;

final class Diagnostics
{
    /** @var list<CheckInterface> */
    private array $checks = [];

    public function add(CheckInterface $check): self
    {
        $this->checks[] = $check;
        return $this;
    }

    /**
     * @return list<CheckResult>
     */
    public function run(): array
    {
        $results = [];
        foreach ($this->checks as $check) {
            try {
                $results[] = $check->run();
            } catch (\Throwable $e) {
                $results[] = new CheckResult(
                    CheckStatus::Error,
                    $check->label(),
                    'Check threw: ' . $e->getMessage(),
                );
            }
        }
        return $results;
    }

    public static function default(string $appRoot): self
    {
        return (new self())
            ->add(new Checks\CmsCoreVersionCheck($appRoot))
            ->add(new Checks\SchemaVersionCheck($appRoot))
            ->add(new Checks\DataDirWritableCheck($appRoot))
            ->add(new Checks\EnvFileCheck($appRoot))
            ->add(new Checks\SiteHealthCheck($appRoot));
    }
}
