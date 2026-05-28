<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Media library with drag-drop upload
 */
require_once EASEO_ROOT . '/includes/media-engine.php';
// JSON API for media picker
if (isset($_GET['action']) && $_GET['action'] === 'list' && isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['files' => get_media()]);
    exit;
}
// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $files = $_FILES['media_file'];
        // Handle multiple files
        if (is_array($files['name'])) {
            $count = count($files['name']);
            $uploaded = 0;
            for ($i = 0; $i < $count; $i++) {
                $file = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                $result = upload_media($file);
                if ($result['success']) {
                    $uploaded++;
                }
            }
            $_SESSION['flash_success'] = Translator::translate('success_files_uploaded', ['count' => $uploaded, 'total' => $count]);
            audit_log('media_upload', "{$uploaded} bestanden geüpload");
        } else {
            $result = upload_media($files);
            if ($result['success']) {
                $_SESSION['flash_success'] = Translator::translate('success_file_uploaded');
                audit_log('media_upload', $files['name']);
            } else {
                $_SESSION['flash_error'] = $result['error'];
            }
        }
    }
    header('Location: /beheer/?tab=media');
    exit;
}
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $id = $_POST['media_id'] ?? '';
        if (delete_media($id)) {
            audit_log('media_verwijderd', "ID: {$id}");
            $_SESSION['flash_success'] = Translator::translate('success_file_deleted');
        } else {
            $_SESSION['flash_error'] = Translator::translate('error_file_delete_failed');
        }
    }
    header('Location: /beheer/?tab=media');
    exit;
}
$media = get_media();
$media = array_reverse($media);
// newest first
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo Translator::translate('media_library_title');
?></h1>
    <span class="text-sm text-gray-500"><?php 
echo count($media);
?> <?php 
echo Translator::translate('unit_files');
?></span>
</div>

<!-- Upload area -->
<div class="admin-card mb-6">
    <form method="POST" enctype="multipart/form-data" id="upload-form">
        <?php 
echo csrf_field();
?>
        <div id="drop-zone" class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer">
            <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="text-gray-400 mb-2"><?php 
echo Translator::translate('media_drop_zone_label');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_media_upload');
?>">?</span></p>
            <p class="text-gray-600 text-sm"><?php 
echo Translator::translate('media_allowed_types_hint');
?></p>
            <input type="file" name="media_file[]" multiple accept="image/*,.pdf" class="hidden" id="file-input">
        </div>
    </form>
</div>

<!-- Media grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
    <?php 
foreach ($media as $item) {
    ?>
    <div class="admin-card p-2 group relative">
        <?php 
    if (str_starts_with($item['type'] ?? '', 'image/')) {
        ?>
            <img src="<?php 
        echo ContentRepository::escape($item['thumb'] ?? $item['url']);
        ?>" alt="<?php 
        echo ContentRepository::escape($item['origineel'] ?? '');
        ?>"
                 class="w-full h-32 object-cover rounded mb-2 cursor-pointer"
                 onclick="copyToClipboard('<?php 
        echo ContentRepository::escape($item['url']);
        ?>')">
        <?php 
    } else {
        ?>
            <div class="w-full h-32 bg-gray-800 rounded mb-2 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        <?php 
    }
    ?>

        <div class="text-xs text-gray-400 truncate" title="<?php 
    echo ContentRepository::escape($item['origineel'] ?? '');
    ?>"><?php 
    echo ContentRepository::escape($item['origineel'] ?? '');
    ?></div>
        <div class="text-xs text-gray-600"><?php 
    echo format_file_size($item['grootte'] ?? 0);
    ?></div>

        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button onclick="copyToClipboard('<?php 
    echo ContentRepository::escape($item['url']);
    ?>')" class="p-1 bg-blue-600 rounded text-white text-xs" title="<?php 
    echo Translator::translate('tooltip_copy_url');
    ?>">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
            </button>
            <form method="POST" class="inline" onsubmit="return confirm('<?php 
    echo Translator::translate('confirm_delete');
    ?>')">
                <?php 
    echo csrf_field();
    ?>
                <input type="hidden" name="media_id" value="<?php 
    echo ContentRepository::escape($item['id']);
    ?>">
                <button type="submit" name="delete_media" class="p-1 bg-red-600 rounded text-white text-xs" title="<?php 
    echo Translator::translate('tooltip_delete_media');
    ?>">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
    </div>
    <?php 
}
?>
</div>

<?php 
if (empty($media)) {
    ?>
<div class="admin-card text-center py-12">
    <p class="text-gray-500"><?php 
    echo Translator::translate('media_no_files');
    ?></p>
</div>
<?php 
}
?>

<script>
var dropZone = document.getElementById('drop-zone');
var fileInput = document.getElementById('file-input');
var form = document.getElementById('upload-form');

dropZone.addEventListener('click', function() { fileInput.click(); });

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) form.submit();
});

dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-blue-500', 'bg-blue-500/5');
});

dropZone.addEventListener('dragleave', function() {
    this.classList.remove('border-blue-500', 'bg-blue-500/5');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-blue-500', 'bg-blue-500/5');
    fileInput.files = e.dataTransfer.files;
    form.submit();
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        var toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg text-sm z-50';
        toast.textContent = '<?php 
echo Translator::translate('toast_url_copied');
?>';
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 2000);
    });
}
</script>
<?php 
