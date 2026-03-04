<?php
/**
 * EASEO CMS — Submission inbox with read/unread
 */

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    if (verify_csrf()) {
        $sid = $_POST['submission_id'] ?? '';
        $file = EASEO_DATA . '/submissions/' . basename($sid) . '.json';
        if (file_exists($file)) {
            $sub = json_decode(file_get_contents($file), true);
            if ($sub) {
                $sub['gelezen'] = true;
                file_put_contents($file, json_encode($sub, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
    header('Location: /beheer/?tab=inbox');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_submission'])) {
    if (verify_csrf()) {
        $sid = $_POST['submission_id'] ?? '';
        $file = EASEO_DATA . '/submissions/' . basename($sid) . '.json';
        if (file_exists($file)) {
            unlink($file);
            $_SESSION['flash_success'] = 'Bericht verwijderd.';
        }
    }
    header('Location: /beheer/?tab=inbox');
    exit;
}

// Load submissions
$subFiles = glob(EASEO_DATA . '/submissions/*.json') ?: [];
$submissions = [];
foreach ($subFiles as $f) {
    $sub = json_decode(file_get_contents($f), true);
    if ($sub) $submissions[] = $sub;
}

// Sort newest first
usort($submissions, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));

// View single submission
$viewId = $_GET['view'] ?? '';
$viewSub = null;
if ($viewId) {
    $viewFile = EASEO_DATA . '/submissions/' . basename($viewId) . '.json';
    if (file_exists($viewFile)) {
        $viewSub = json_decode(file_get_contents($viewFile), true);
        // Mark as read
        if ($viewSub && empty($viewSub['gelezen'])) {
            $viewSub['gelezen'] = true;
            file_put_contents($viewFile, json_encode($viewSub, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Inbox</h1>
    <span class="text-sm text-gray-500"><?= count($submissions) ?> berichten</span>
</div>

<?php if ($viewSub): ?>
<!-- Single submission view -->
<div class="admin-card mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-white"><?= e($viewSub['formulier_naam'] ?? '') ?></h2>
            <p class="text-sm text-gray-500"><?= e($viewSub['datum'] ?? '') ?> — IP: <?= e($viewSub['ip'] ?? '') ?></p>
        </div>
        <a href="/beheer/?tab=inbox" class="btn-admin btn-admin-outline text-sm">&larr; Terug</a>
    </div>

    <div class="space-y-3">
        <?php foreach (($viewSub['data'] ?? []) as $key => $value): ?>
        <div>
            <span class="text-sm text-gray-500"><?= e(ucfirst($key)) ?>:</span>
            <div class="text-white mt-0.5"><?= nl2br(e($value)) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="flex gap-2 mt-6 pt-4 border-t border-gray-700">
        <?php if (!empty($viewSub['data']['email'])): ?>
        <a href="mailto:<?= e($viewSub['data']['email']) ?>" class="btn-admin btn-admin-primary text-sm">Beantwoorden</a>
        <?php endif; ?>
        <form method="POST" class="inline" onsubmit="return confirm('Verwijderen?')">
            <?= csrf_field() ?>
            <input type="hidden" name="submission_id" value="<?= e($viewSub['id'] ?? '') ?>">
            <button type="submit" name="delete_submission" class="btn-admin btn-admin-danger text-sm">Verwijderen</button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Submission list -->
<div class="admin-card">
    <?php if (empty($submissions)): ?>
        <p class="text-gray-500">Nog geen berichten ontvangen.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:20px"></th>
                <th>Formulier</th>
                <th>Afzender</th>
                <th>Datum</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $sub): ?>
            <tr class="<?= empty($sub['gelezen']) ? 'font-semibold' : '' ?>">
                <td><?= empty($sub['gelezen']) ? '<span class="unread-dot"></span>' : '' ?></td>
                <td>
                    <a href="/beheer/?tab=inbox&view=<?= e($sub['id']) ?>" class="text-white hover:text-blue-400">
                        <?= e($sub['formulier_naam'] ?? 'Onbekend') ?>
                    </a>
                </td>
                <td class="text-gray-400">
                    <?= e($sub['data']['naam'] ?? $sub['data']['email'] ?? '—') ?>
                </td>
                <td class="text-gray-500"><?= e($sub['datum'] ?? '') ?></td>
                <td class="text-right">
                    <form method="POST" class="inline" onsubmit="return confirm('Verwijderen?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="submission_id" value="<?= e($sub['id']) ?>">
                        <button type="submit" name="delete_submission" class="text-red-400 hover:text-red-300 text-sm">Verwijderen</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php endif; ?>
