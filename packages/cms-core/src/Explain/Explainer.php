<?php
declare(strict_types=1);

namespace Easeo\Cms\Explain;

final class Explainer
{
    private readonly string $topicsDir;

    public function __construct(?string $topicsDir = null)
    {
        $this->topicsDir = $topicsDir ?? dirname(__DIR__, 2) . '/explain';
    }

    /**
     * @return list<string>
     */
    public function topics(): array
    {
        $files = glob($this->topicsDir . '/*.md') ?: [];
        sort($files);
        return array_values(array_map(fn(string $f) => basename($f, '.md'), $files));
    }

    /**
     * Render a topic — returns raw markdown content.
     * If topic not found, returns a human-readable error with Levenshtein suggestion.
     */
    public function render(string $topic): string
    {
        $file = $this->topicsDir . '/' . $topic . '.md';
        if (is_file($file)) {
            return (string) file_get_contents($file);
        }

        // Suggest closest topic via Levenshtein
        $best         = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($this->topics() as $t) {
            $d = levenshtein($topic, $t);
            if ($d < $bestDistance) {
                $bestDistance = $d;
                $best         = $t;
            }
        }

        $hint = ($best !== null && $bestDistance <= 5) ? " Did you mean '$best'?" : '';
        return "Topic '$topic' not found." . $hint . "\n\nAvailable topics: " . implode(', ', $this->topics()) . "\n";
    }
}
