<?php
declare(strict_types=1);

namespace Easeo\Cms\Content;

/**
 * EASEO CMS — YouTube in content.
 *
 * Een losse YouTube-link op een eigen regel (ook Shorts en youtu.be), of een
 * bestaande YouTube-<iframe>, wordt een voorvertoning: thumbnail + afspeelknop.
 * De speler laadt pas na een klik, via youtube-nocookie — geen YouTube-javascript
 * en geen cookies voor wie niet kijkt. Shorts 9:16, de rest 16:9.
 *
 * Gebruik: echo VideoEmbed::render($html); en eenmaal per pagina VideoEmbed::assets().
 * Vereist CSP: frame-src https://www.youtube-nocookie.com
 */
class VideoEmbed {
    private const URL = 'https?://(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|shorts/|embed/|live/)|youtu\.be/)([\w-]{11})(?![\w-])[^\s<"\']*';

    public static function render(string $html): string {
        $iframe = '~<iframe\b[^>]*\bsrc=["\']([^"\']*)["\'][^>]*>\s*</iframe>~i';
        $html = (string) preg_replace_callback($iframe, fn($m) => stripos($m[1], 'youtube') !== false ? self::block($m[1]) : $m[0], $html);
        $line = '~^\h*(?:<p>)?\h*(?:<a\b[^>]*>)?\h*(' . self::URL . ')\h*(?:</a>)?\h*(?:</p>)?\h*(?:<br\s*/?>)?\h*\r?$~im';
        return (string) preg_replace_callback($line, fn($m) => self::block($m[1]), $html);
    }

    /** Een YouTube-adres zonder geldig video-ID (oude Blogger-video's) valt weg. */
    public static function block(string $url): string {
        if (!preg_match('~' . self::URL . '~i', $url, $m)) return '';
        $id = $m[1];
        $short = stripos($url, '/shorts/') !== false;
        $watch = $short ? "https://www.youtube.com/shorts/$id" : "https://www.youtube.com/watch?v=$id";
        return '<div class="easeo-video' . ($short ? ' easeo-video--short' : '') . '">'
             . '<a href="' . $watch . '" data-video="' . $id . '" target="_blank" rel="noopener" aria-label="Video afspelen">'
             . '<img src="https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg" alt="" loading="lazy" decoding="async"></a></div>';
    }

    public static function assets(): string {
        return <<<'HTML'
<style>
.easeo-video{position:relative;aspect-ratio:16/9;margin:2rem 0;background:#000;border-radius:12px;overflow:hidden}
.easeo-video--short{aspect-ratio:9/16;max-width:360px;margin-inline:auto}
.easeo-video a{display:block;height:100%;border:0}
.easeo-video img,.easeo-video iframe{display:block;width:100%;height:100%;margin:0;object-fit:cover;border:0;border-radius:0;box-shadow:none}
.easeo-video a::after{content:"";position:absolute;top:50%;left:50%;width:68px;height:48px;transform:translate(-50%,-50%);border-radius:12px;transition:background-color .2s;background:rgba(0,0,0,.7) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23fff' d='M8 5v14l11-7z'/%3E%3C/svg%3E") center/26px no-repeat}
.easeo-video a:hover::after,.easeo-video a:focus-visible::after{background-color:#e00}
</style>
<script>
document.addEventListener('click', function (event) {
  var link = event.target.closest('a[data-video]');
  if (!link) return;
  event.preventDefault();
  var frame = document.createElement('iframe');
  frame.src = 'https://www.youtube-nocookie.com/embed/' + link.dataset.video + '?autoplay=1&playsinline=1&rel=0';
  frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
  frame.title = 'YouTube-video';
  link.replaceWith(frame);
});
</script>
HTML;
    }
}
