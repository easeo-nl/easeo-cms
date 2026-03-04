<?php
/**
 * EASEO CMS — Audit log viewer (admin only)
 */
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$entries = read_audit_log($perPage + 1, $offset);
$hasMore = count($entries) > $perPage;
if ($hasMore) array_pop($entries);
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Activiteitenlog</h1>
</div>

<div class="admin-card">
    <?php if (empty($entries)): ?>
        <p class="text-gray-500">Geen activiteit gevonden.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Datum</th>
                <th>Gebruiker</th>
                <th>IP</th>
                <th>Actie</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td class="text-gray-400 whitespace-nowrap"><?= e($entry['datum'] ?? '') ?></td>
                <td class="text-white"><?= e($entry['gebruiker'] ?? '') ?></td>
                <td class="text-gray-500 font-mono text-xs"><?= e($entry['ip'] ?? '') ?></td>
                <td><span class="badge badge-primary"><?= e($entry['actie'] ?? '') ?></span></td>
                <td class="text-gray-400"><?= e($entry['details'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-700">
        <?php if ($page > 1): ?>
            <a href="/beheer/?tab=activiteit&p=<?= $page - 1 ?>" class="btn-admin btn-admin-outline text-sm">&laquo; Vorige</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>

        <span class="text-sm text-gray-500">Pagina <?= $page ?></span>

        <?php if ($hasMore): ?>
            <a href="/beheer/?tab=activiteit&p=<?= $page + 1 ?>" class="btn-admin btn-admin-outline text-sm">Volgende &raquo;</a>
        <?php else: ?>
            <span></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
