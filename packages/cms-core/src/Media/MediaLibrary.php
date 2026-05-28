<?php
declare(strict_types=1);

namespace Easeo\Cms\Media;

use Easeo\Cms\Audit\AuditLogger;
use Easeo\Cms\Content\ContentRepository;

final class MediaLibrary
{
    private const MAX_SIZE      = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'application/pdf'];
    private const THUMB_WIDTH   = 300;
    private const THUMB_HEIGHT  = 300;
    private const MAX_WIDTH     = 1920;

    // -------------------------------------------------------------------------
    // Media CRUD (media.json)
    // -------------------------------------------------------------------------

    public static function getMedia(): array
    {
        $data = ContentRepository::loadJson('media.json');
        return $data['files'] ?? [];
    }

    public static function saveMedia(array $files): bool
    {
        $result = ContentRepository::saveJson('media.json', ['files' => $files]);
        ContentRepository::invalidateJsonCache('media.json');
        return $result;
    }

    // -------------------------------------------------------------------------
    // Upload
    // -------------------------------------------------------------------------

    /**
     * @param  array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @return array{success:bool,file?:array<string,mixed>,error?:string}
     */
    public static function uploadMedia(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload fout: code ' . $file['error']];
        }

        if ($file['size'] > self::MAX_SIZE) {
            return ['success' => false, 'error' => 'Bestand is te groot (max 10MB).'];
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return ['success' => false, 'error' => 'Ongeldig bestandstype: ' . $mimeType];
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
        if (!$ext) {
            $ext = 'jpg';
        }

        // Block dangerous double extensions (e.g. "photo.php.jpg")
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];
        if (!in_array($ext, $allowedExtensions, true)) {
            return ['success' => false, 'error' => 'Bestandsextensie niet toegestaan: .' . $ext];
        }

        // Check for PHP extensions anywhere in the filename
        $nameLower = strtolower($file['name']);
        if (preg_match('/\.ph(p[0-9]?|tml|ps?)\b/i', pathinfo($nameLower, PATHINFO_FILENAME))) {
            return ['success' => false, 'error' => 'Bestandsnaam bevat een niet-toegestane extensie.'];
        }

        $safeName = preg_replace('/[^a-z0-9-]/', '', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
        if (empty($safeName)) {
            $safeName = 'bestand';
        }

        $filename = $safeName . '-' . substr(md5(uniqid()), 0, 8) . '.' . $ext;

        // SVG sanitization — strip scripts and event handlers
        if ($ext === 'svg' || $mimeType === 'image/svg+xml') {
            $svgContent = file_get_contents($file['tmp_name']);
            $svgContent = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $svgContent);
            $svgContent = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $svgContent);
            $svgContent = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $svgContent);
            file_put_contents($file['tmp_name'], $svgContent);
        }

        $uploadDir = defined('MEDIA_UPLOAD_DIR') ? constant('MEDIA_UPLOAD_DIR') : (defined('EASEO_ROOT') ? constant('EASEO_ROOT') . '/images/uploads' : '');
        $thumbDir  = defined('MEDIA_THUMB_DIR')  ? constant('MEDIA_THUMB_DIR')  : (defined('EASEO_ROOT') ? constant('EASEO_ROOT') . '/images/thumbs'  : '');

        $uploadPath = $uploadDir . '/' . $filename;
        $thumbPath  = $thumbDir  . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => false, 'error' => 'Kon bestand niet opslaan.'];
        }

        AuditLogger::log('bestand_geupload', "Bestand: {$filename} ({$mimeType})");

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            self::resizeImage($uploadPath, self::MAX_WIDTH);
            self::createThumbnail($uploadPath, $thumbPath, self::THUMB_WIDTH, self::THUMB_HEIGHT);
        }

        $media = self::getMedia();
        $entry = [
            'id'           => substr(md5(uniqid((string) mt_rand(), true)), 0, 12),
            'bestandsnaam' => $filename,
            'origineel'    => $file['name'],
            'type'         => $mimeType,
            'grootte'      => filesize($uploadPath),
            'url'          => '/images/uploads/' . $filename,
            'thumb'        => file_exists($thumbPath) ? '/images/thumbs/' . $filename : '/images/uploads/' . $filename,
            'datum'        => date('Y-m-d H:i:s'),
        ];
        $media[] = $entry;
        self::saveMedia($media);

        return ['success' => true, 'file' => $entry];
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    public static function deleteMedia(string $id): bool
    {
        $media = self::getMedia();

        foreach ($media as $idx => $item) {
            if ($item['id'] === $id) {
                $bestandsnaam = $item['bestandsnaam'] ?? '';

                if ($bestandsnaam !== '') {
                    $uploadDir = defined('MEDIA_UPLOAD_DIR') ? constant('MEDIA_UPLOAD_DIR') : (defined('EASEO_ROOT') ? constant('EASEO_ROOT') . '/images/uploads' : '');
                    $thumbDir  = defined('MEDIA_THUMB_DIR')  ? constant('MEDIA_THUMB_DIR')  : (defined('EASEO_ROOT') ? constant('EASEO_ROOT') . '/images/thumbs'  : '');

                    if ($uploadDir !== '') {
                        $uploadFile = $uploadDir . '/' . $bestandsnaam;
                        if (is_file($uploadFile)) {
                            unlink($uploadFile);
                        }
                    }
                    if ($thumbDir !== '') {
                        $thumbFile = $thumbDir . '/' . $bestandsnaam;
                        if (is_file($thumbFile)) {
                            unlink($thumbFile);
                        }
                    }
                }

                array_splice($media, $idx, 1);
                self::saveMedia($media);
                AuditLogger::log('bestand_verwijderd', "Bestand: {$bestandsnaam}");
                return true;
            }
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Image processing (GD)
    // -------------------------------------------------------------------------

    public static function resizeImage(string $path, int $maxWidth): void
    {
        $info = getimagesize($path);
        if (!$info || $info[0] <= $maxWidth) {
            return;
        }

        $src = self::createImageFromFile($path, $info['mime']);
        if (!$src) {
            return;
        }

        $origW = $info[0];
        $origH = $info[1];
        $newW  = $maxWidth;
        $newH  = (int) round($origH * ($newW / $origW));

        $dst = imagecreatetruecolor($newW, $newH);
        self::preserveTransparency($dst, $info['mime']);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        self::saveImage($dst, $path, $info['mime']);
        imagedestroy($src);
        imagedestroy($dst);
    }

    public static function createThumbnail(string $srcPath, string $dstPath, int $width, int $height): void
    {
        $info = getimagesize($srcPath);
        if (!$info) {
            return;
        }

        $src = self::createImageFromFile($srcPath, $info['mime']);
        if (!$src) {
            return;
        }

        $origW = $info[0];
        $origH = $info[1];
        $ratio = max($width / $origW, $height / $origH);
        $cropW = (int) round($width  / $ratio);
        $cropH = (int) round($height / $ratio);
        $cropX = (int) round(($origW - $cropW) / 2);
        $cropY = (int) round(($origH - $cropH) / 2);

        $dst = imagecreatetruecolor($width, $height);
        self::preserveTransparency($dst, $info['mime']);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);
        self::saveImage($dst, $dstPath, $info['mime']);
        imagedestroy($src);
        imagedestroy($dst);
    }

    /**
     * @return \GdImage|false
     */
    public static function createImageFromFile(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => false,
        };
    }

    /**
     * @param \GdImage $img
     */
    public static function saveImage($img, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($img, $path, 85),
            'image/png'  => imagepng($img, $path, 8),
            'image/gif'  => imagegif($img, $path),
            'image/webp' => imagewebp($img, $path, 85),
            default      => null,
        };
    }

    /**
     * @param \GdImage $img
     */
    public static function preserveTransparency($img, string $mime): void
    {
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $transparent);
        }
    }

    // -------------------------------------------------------------------------
    // Formatting helper
    // -------------------------------------------------------------------------

    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
