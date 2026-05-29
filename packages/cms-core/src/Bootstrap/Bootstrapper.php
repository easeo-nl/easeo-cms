<?php
declare(strict_types=1);

namespace Easeo\Cms\Bootstrap;

final class Bootstrapper
{
    public function __construct(private readonly string $appRoot) {}

    /**
     * Initialise data/ on first deploy. Returns the list of files created.
     * Idempotent — skips files that already exist.
     *
     * @return list<string>
     */
    public function bootstrap(): array
    {
        $dataDir = $this->appRoot . '/data';
        $templateFile = $this->appRoot . '/site.template.json';

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $created = [];

        $siteFile = "$dataDir/site.json";
        if (!is_file($siteFile) && is_file($templateFile)) {
            copy($templateFile, $siteFile);
            $created[] = 'site.json';
        }

        // Initialise the standard skeleton files with safe defaults
        $defaults = [
            'pages.json'      => [],
            'posts.json'      => ['posts' => [], 'categories' => []],
            'navigation.json' => [],
            'forms.json'      => [],
            'media.json'      => [],
            'legal.json'      => new \stdClass(),
            'users.json'      => [],
        ];

        foreach ($defaults as $name => $default) {
            $file = "$dataDir/$name";
            if (is_file($file)) {
                continue;
            }
            file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $created[] = $name;
        }

        return $created;
    }
}
