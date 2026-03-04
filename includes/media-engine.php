<?php
/**
 * EASEO CMS — Media engine: upload, resize, thumbnail, CRUD
 * Uses media.json with structure: {files:[]}
 */
require_once __DIR__ . '/content.php';

define('MEDIA_UPLOAD_DIR', EASEO_ROOT . '/images/uploads');
define('MEDIA_THUMB_DIR', EASEO_ROOT . '/images/thumbs');
define('MEDIA_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('MEDIA_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'application/pdf']);
define('MEDIA_THUMB_WIDTH', 300);
define('MEDIA_THUMB_HEIGHT', 300);
define('MEDIA_MAX_WIDTH', 1920);

function get_media(): array {
    $data = load_json('media.json');
    return $data['files'] ?? [];
}

function save_media(array $files): bool {
    return save_json('media.json', ['files' => $files]);
}

function upload_media(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload fout: code ' . $file['error']];
    }

    if ($file['size'] > MEDIA_MAX_SIZE) {
        return ['success' => false, 'error' => 'Bestand is te groot (max 10MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, MEDIA_ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Ongeldig bestandstype: ' . $mimeType];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
    if (!$ext) $ext = 'jpg';
    $safeName = preg_replace('/[^a-z0-9-]/', '', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
    $filename = $safeName . '-' . substr(md5(uniqid()), 0, 8) . '.' . $ext;

    $uploadPath = MEDIA_UPLOAD_DIR . '/' . $filename;
    $thumbPath = MEDIA_THUMB_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => false, 'error' => 'Kon bestand niet opslaan.'];
    }

    if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
        resize_image($uploadPath, MEDIA_MAX_WIDTH);
        create_thumbnail($uploadPath, $thumbPath, MEDIA_THUMB_WIDTH, MEDIA_THUMB_HEIGHT);
    }

    $media = get_media();
    $entry = [
        'id' => substr(md5(uniqid(mt_rand(), true)), 0, 12),
        'bestandsnaam' => $filename,
        'origineel' => $file['name'],
        'type' => $mimeType,
        'grootte' => filesize($uploadPath),
        'url' => '/images/uploads/' . $filename,
        'thumb' => file_exists($thumbPath) ? '/images/thumbs/' . $filename : '/images/uploads/' . $filename,
        'datum' => date('Y-m-d H:i:s'),
    ];
    $media[] = $entry;
    save_media($media);

    return ['success' => true, 'file' => $entry];
}

function delete_media(string $id): bool {
    $media = get_media();
    foreach ($media as $idx => $item) {
        if ($item['id'] === $id) {
            $uploadFile = MEDIA_UPLOAD_DIR . '/' . $item['bestandsnaam'];
            $thumbFile = MEDIA_THUMB_DIR . '/' . $item['bestandsnaam'];
            if (file_exists($uploadFile)) unlink($uploadFile);
            if (file_exists($thumbFile)) unlink($thumbFile);

            array_splice($media, $idx, 1);
            save_media($media);
            return true;
        }
    }
    return false;
}

function resize_image(string $path, int $maxWidth): void {
    $info = getimagesize($path);
    if (!$info || $info[0] <= $maxWidth) return;

    $src = create_image_from_file($path, $info['mime']);
    if (!$src) return;

    $origW = $info[0];
    $origH = $info[1];
    $newW = $maxWidth;
    $newH = (int)round($origH * ($newW / $origW));

    $dst = imagecreatetruecolor($newW, $newH);
    preserve_transparency($dst, $info['mime']);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    save_image($dst, $path, $info['mime']);
    imagedestroy($src);
    imagedestroy($dst);
}

function create_thumbnail(string $srcPath, string $dstPath, int $width, int $height): void {
    $info = getimagesize($srcPath);
    if (!$info) return;

    $src = create_image_from_file($srcPath, $info['mime']);
    if (!$src) return;

    $origW = $info[0];
    $origH = $info[1];

    $ratio = max($width / $origW, $height / $origH);
    $cropW = (int)round($width / $ratio);
    $cropH = (int)round($height / $ratio);
    $cropX = (int)round(($origW - $cropW) / 2);
    $cropY = (int)round(($origH - $cropH) / 2);

    $dst = imagecreatetruecolor($width, $height);
    preserve_transparency($dst, $info['mime']);
    imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $width, $height, $cropW, $cropH);

    save_image($dst, $dstPath, $info['mime']);
    imagedestroy($src);
    imagedestroy($dst);
}

function create_image_from_file(string $path, string $mime) {
    return match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($path),
        'image/png' => imagecreatefrompng($path),
        'image/gif' => imagecreatefromgif($path),
        'image/webp' => imagecreatefromwebp($path),
        default => null,
    };
}

function save_image($img, string $path, string $mime): void {
    match ($mime) {
        'image/jpeg' => imagejpeg($img, $path, 85),
        'image/png' => imagepng($img, $path, 8),
        'image/gif' => imagegif($img, $path),
        'image/webp' => imagewebp($img, $path, 85),
        default => null,
    };
}

function preserve_transparency($img, string $mime): void {
    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'])) {
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
    }
}

function format_file_size(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}
