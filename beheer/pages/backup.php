<?php
/**
 * EASEO CMS — Backup download & restore (admin only)
 */

// Handle download backup
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
        header('Location: /beheer/?tab=backup');
        exit;
    }

    $zipFile = tempnam(sys_get_temp_dir(), 'easeo_backup_');
    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== true) {
        $_SESSION['flash_error'] = 'Kon backup niet aanmaken.';
        header('Location: /beheer/?tab=backup');
        exit;
    }

    // Add all JSON files from data/
    foreach (glob(EASEO_DATA . '/*.json') as $file) {
        $zip->addFile($file, 'data/' . basename($file));
    }

    // Add submissions
    foreach (glob(EASEO_DATA . '/submissions/*.json') as $file) {
        $zip->addFile($file, 'data/submissions/' . basename($file));
    }

    // Add audit log
    if (file_exists(EASEO_DATA . '/audit.log')) {
        $zip->addFile(EASEO_DATA . '/audit.log', 'data/audit.log');
    }

    $zip->close();

    $filename = 'easeo-backup-' . date('Y-m-d-His') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($zipFile));
    readfile($zipFile);
    unlink($zipFile);

    audit_log('backup_gedownload', $filename);
    exit;
}

// Handle restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } elseif (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash_error'] = 'Upload fout.';
    } else {
        $file = $_FILES['backup_file'];
        $zip = new ZipArchive();

        if ($zip->open($file['tmp_name']) !== true) {
            $_SESSION['flash_error'] = 'Ongeldig ZIP bestand.';
        } else {
            // Validate contents — must contain data/ JSON files
            $valid = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'data/') && str_ends_with($name, '.json')) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                $_SESSION['flash_error'] = 'Ongeldige backup: geen data bestanden gevonden.';
            } else {
                // Extract JSON files only (safety measure)
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);

                    // Only allow data/ files
                    if (!str_starts_with($name, 'data/')) continue;

                    // Prevent path traversal
                    if (str_contains($name, '..')) continue;

                    $targetPath = EASEO_ROOT . '/' . $name;
                    $targetDir = dirname($targetPath);
                    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

                    $content = $zip->getFromIndex($i);
                    if ($content !== false) {
                        file_put_contents($targetPath, $content);
                    }
                }

                $zip->close();
                audit_log('backup_hersteld', $file['name']);
                $_SESSION['flash_success'] = 'Backup hersteld. Data is bijgewerkt.';
            }
        }
    }
    header('Location: /beheer/?tab=backup');
    exit;
}

// Get data file info
$dataFiles = glob(EASEO_DATA . '/*.json') ?: [];
$totalSize = 0;
foreach ($dataFiles as $f) $totalSize += filesize($f);
$subFiles = glob(EASEO_DATA . '/submissions/*.json') ?: [];
?>

<h1 class="text-2xl font-bold text-white mb-6">Backup & Herstel</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Download backup -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Backup downloaden</h2>
        <p class="text-sm text-gray-400 mb-4">Download een ZIP met alle data bestanden (content, gebruikers, instellingen, formulieren, berichten).</p>

        <div class="text-sm text-gray-500 mb-4">
            <p><?= count($dataFiles) ?> data bestanden</p>
            <p><?= count($subFiles) ?> formulier inzendingen</p>
            <p>Totale grootte: ~<?= round($totalSize / 1024, 1) ?> KB</p>
        </div>

        <a href="/beheer/?tab=backup&action=download&csrf_token=<?= csrf_token() ?>" class="btn-admin btn-admin-primary">
            Download backup
        </a>
        <p class="text-xs text-gray-500 mt-3"><span class="help-tooltip" data-help="Download een kopie van alle content, instellingen en uploads. Bewaar deze op een veilige plek.">?</span> Tip: bewaar backups op een veilige plek.</p>
    </div>

    <!-- Restore backup -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Backup herstellen</h2>
        <p class="text-sm text-gray-400 mb-4">Upload een eerder gedownloade backup ZIP om alle data te herstellen. <span class="help-tooltip" data-help="Herstel een eerdere backup. Let op: dit overschrijft alle huidige content en instellingen.">?</span></p>

        <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Weet u zeker? Dit overschrijft alle huidige data.')">
            <?= csrf_field() ?>
            <input type="file" name="backup_file" accept=".zip" required class="admin-input w-full mb-3">
            <button type="submit" name="restore_backup" class="btn-admin btn-admin-danger">Backup herstellen</button>
        </form>
    </div>
</div>
