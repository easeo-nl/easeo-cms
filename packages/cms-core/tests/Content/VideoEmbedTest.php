<?php
namespace Easeo\Cms\Tests\Content;

use Easeo\Cms\Content\VideoEmbed;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase {
    public function testShortOnOwnLineBecomesVertical(): void {
        $html = VideoEmbed::render("<p>Kijk:</p>\r\nhttps://youtube.com/shorts/QhN2qkEMak8?si=abc\r\n<p>Einde</p>");
        $this->assertStringContainsString('easeo-video--short', $html);
        $this->assertStringContainsString('data-video="QhN2qkEMak8"', $html);
        $this->assertStringContainsString('<p>Kijk:</p>', $html);
        $this->assertStringContainsString('<p>Einde</p>', $html);
    }

    public function testWatchLinkInParagraphBecomesLandscape(): void {
        $url = 'https://www.youtube.com/watch?v=zGaR-sShQ9Y&t=3';
        $html = VideoEmbed::render("<p><a href=\"$url\">$url</a></p>");
        $this->assertStringContainsString('class="easeo-video"', $html);
        $this->assertStringNotContainsString('<p>', $html);
    }

    public function testShortLinkDomain(): void {
        $this->assertStringContainsString('data-video="pEx7yTEzHJM"', VideoEmbed::render('https://youtu.be/pEx7yTEzHJM'));
    }

    public function testYoutubeIframeBecomesPreview(): void {
        $html = VideoEmbed::render('<iframe width="320" src="https://www.youtube.com/embed/QK57Rrl2Kro?feature=player_embedded" allowfullscreen></iframe>');
        $this->assertStringContainsString('data-video="QK57Rrl2Kro"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function testYoutubeIframeWithoutValidIdIsDropped(): void {
        $this->assertSame('', VideoEmbed::render('<iframe src="https://www.youtube.com/embed/6464151501260737570?x=1"></iframe>'));
    }

    public function testLeavesOtherContentAlone(): void {
        $untouched = [
            '<p><a href="https://www.youtube.com/shorts/QhN2qkEMak8">Bekijk de video</a></p>',
            '<p>Zie https://youtu.be/pEx7yTEzHJM voor meer.</p>',
            '<iframe src="https://www.google.com/maps/embed?pb=1"></iframe>',
        ];
        foreach ($untouched as $html) {
            $this->assertSame($html, VideoEmbed::render($html));
        }
    }

    public function testWorksOnEscapedPlainText(): void {
        $html = VideoEmbed::render(nl2br(htmlspecialchars("Hallo\nhttps://www.youtube.com/watch?v=zGaR-sShQ9Y&t=3\nDoei")));
        $this->assertStringContainsString('data-video="zGaR-sShQ9Y"', $html);
        $this->assertStringContainsString('Doei', $html);
    }

    public function testMarkupOnlyCarriesTheId(): void {
        $this->assertStringNotContainsString('onmouseover', VideoEmbed::block('https://youtu.be/pEx7yTEzHJM"onmouseover=x'));
    }
}
