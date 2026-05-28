<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Media;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Media\MediaLibrary;
use PHPUnit\Framework\TestCase;

final class MediaLibraryTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/media-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
        ContentRepository::resetCache();
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        ContentRepository::resetCache();
        if (is_dir($this->tmpDataDir)) {
            $this->rmrf($this->tmpDataDir);
        }
    }

    private function rmrf(string $dir): void
    {
        foreach (glob("$dir/*") ?: [] as $f) {
            is_dir($f) ? $this->rmrf($f) : unlink($f);
        }
        rmdir($dir);
    }

    public function test_get_media_returns_empty_when_no_file(): void
    {
        $this->assertSame([], MediaLibrary::getMedia());
    }

    public function test_save_then_get_round_trip(): void
    {
        $files = [['id' => 'a', 'naam' => 'foo.png', 'pad' => '/uploads/foo.png']];
        $this->assertTrue(MediaLibrary::saveMedia($files));
        $this->assertSame($files, MediaLibrary::getMedia());
    }

    public function test_format_file_size_bytes(): void
    {
        $this->assertSame('512 B', MediaLibrary::formatFileSize(512));
    }

    public function test_format_file_size_kilobytes(): void
    {
        $result = MediaLibrary::formatFileSize(2048);
        $this->assertStringContainsString('KB', $result);
    }

    public function test_format_file_size_megabytes(): void
    {
        $result = MediaLibrary::formatFileSize(5 * 1024 * 1024);
        $this->assertStringContainsString('MB', $result);
    }

    public function test_delete_media_removes_entry_by_id(): void
    {
        MediaLibrary::saveMedia([
            ['id' => 'a', 'naam' => 'a.png'],
            ['id' => 'b', 'naam' => 'b.png'],
        ]);
        // Test only the metadata deletion (the file may not exist in uploads dir).
        $this->assertTrue(MediaLibrary::deleteMedia('a'));
        $media = MediaLibrary::getMedia();
        $this->assertCount(1, $media);
        $this->assertSame('b', $media[0]['id']);
    }

    public function test_delete_media_returns_false_for_unknown_id(): void
    {
        MediaLibrary::saveMedia([['id' => 'a', 'naam' => 'a.png']]);
        $this->assertFalse(MediaLibrary::deleteMedia('does-not-exist'));
    }

    public function test_resize_image_on_small_png_does_not_throw(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD not available');
        }
        // Create a 50x50 PNG
        $img = imagecreatetruecolor(50, 50);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 0, 0));
        $path = "{$this->tmpDataDir}/test.png";
        imagepng($img, $path);
        imagedestroy($img);

        // maxWidth larger than image — should be no-op (no error)
        MediaLibrary::resizeImage($path, 200);
        $this->assertFileExists($path);
        [$w, $h] = getimagesize($path);
        $this->assertSame(50, $w, 'no resize when image smaller than maxWidth');
    }

    public function test_resize_image_shrinks_when_image_wider_than_max(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD not available');
        }
        $img = imagecreatetruecolor(400, 200);
        imagefill($img, 0, 0, imagecolorallocate($img, 0, 255, 0));
        $path = "{$this->tmpDataDir}/wide.png";
        imagepng($img, $path);
        imagedestroy($img);

        MediaLibrary::resizeImage($path, 100);
        [$w, $h] = getimagesize($path);
        $this->assertSame(100, $w);
        $this->assertSame(50, $h, 'aspect ratio preserved');
    }

    public function test_create_thumbnail_produces_target_dims(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD not available');
        }
        $img = imagecreatetruecolor(400, 200);
        imagefill($img, 0, 0, imagecolorallocate($img, 0, 0, 255));
        $src = "{$this->tmpDataDir}/src.png";
        $dst = "{$this->tmpDataDir}/thumb.png";
        imagepng($img, $src);
        imagedestroy($img);

        MediaLibrary::createThumbnail($src, $dst, 100, 50);
        $this->assertFileExists($dst);
        [$w, $h] = getimagesize($dst);
        $this->assertSame(100, $w);
        $this->assertSame(50, $h);
    }
}
