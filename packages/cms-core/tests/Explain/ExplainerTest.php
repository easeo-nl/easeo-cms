<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Explain;

use Easeo\Cms\Explain\Explainer;
use PHPUnit\Framework\TestCase;

final class ExplainerTest extends TestCase
{
    private Explainer $explainer;

    protected function setUp(): void
    {
        // Use the real topics dir (packages/cms-core/explain/)
        $topicsDir      = dirname(__DIR__, 2) . '/explain';
        $this->explainer = new Explainer($topicsDir);
    }

    public function testTopicsReturnsAtLeastOneKnownTopic(): void
    {
        $topics = $this->explainer->topics();
        $this->assertNotEmpty($topics);
        $this->assertContains('deploys', $topics);
    }

    public function testRenderDeploysContainsAtomicSwap(): void
    {
        $content = $this->explainer->render('deploys');
        $this->assertStringContainsStringIgnoringCase('atomic', $content);
    }

    public function testUnknownTopicReturnsSuggestionMessage(): void
    {
        // 'deplo' is close to 'deploys' (Levenshtein = 2)
        $result = $this->explainer->render('deplo');
        $this->assertStringContainsString('not found', $result);
        $this->assertStringContainsString('deploys', $result);
    }

    public function testTopicsListsAllSevenExpectedTopics(): void
    {
        $topics   = $this->explainer->topics();
        $expected = ['backups', 'dependabot', 'deploys', 'migrations', 'rollback', 'state', 'troubleshooting'];

        foreach ($expected as $e) {
            $this->assertContains($e, $topics, "Expected topic '$e' to exist");
        }
    }
}
