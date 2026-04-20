<?php
use Easeo\Cms\Content\ContentRepository;
/**
 * EASEO CMS — Audit log viewer (admin only)
 */
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;
$entries = read_audit_log($perPage + 1, $offset);
$hasMore = count($entries) > $perPage;
if ($hasMore) {
    array_pop($entries);
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo t('activity_log_title');
?></h1>
</div>

<div class="admin-card">
    <?php 
if (empty($entries)) {
    ?>
        <p class="text-gray-500"><?php 
    echo t('no_activity_found');
    ?></p>
    <?php 
} else {
    ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php 
    echo t('table_header_date');
    ?></th>
                <th><?php 
    echo t('table_header_user');
    ?></th>
                <th><?php 
    echo t('table_header_ip');
    ?></th>
                <th><?php 
    echo t('table_header_action');
    ?></th>
                <th><?php 
    echo t('table_header_details');
    ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
    foreach ($entries as $entry) {
        ?>
            <tr>
                <td class="text-gray-400 whitespace-nowrap"><?php 
        echo ContentRepository::escape($entry['datum'] ?? '');
        ?></td>
                <td class="text-white"><?php 
        echo ContentRepository::escape($entry['gebruiker'] ?? '');
        ?></td>
                <td class="text-gray-500 font-mono text-xs"><?php 
        echo ContentRepository::escape($entry['ip'] ?? '');
        ?></td>
                <td><span class="badge badge-primary"><?php 
        echo ContentRepository::escape($entry['actie'] ?? '');
        ?></span></td>
                <td class="text-gray-400"><?php 
        echo ContentRepository::escape($entry['details'] ?? '');
        ?></td>
            </tr>
            <?php 
    }
    ?>
        </tbody>
    </table>

    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-700">
        <?php 
    if ($page > 1) {
        ?>
            <a href="/beheer/?tab=activiteit&p=<?php 
        echo $page - 1;
        ?>" class="btn-admin btn-admin-outline text-sm">&laquo; <?php 
        echo t('pagination_previous');
        ?></a>
        <?php 
    } else {
        ?>
            <span></span>
        <?php 
    }
    ?>

        <span class="text-sm text-gray-500">Pagina <?php 
    echo $page;
    ?></span>

        <?php 
    if ($hasMore) {
        ?>
            <a href="/beheer/?tab=activiteit&p=<?php 
        echo $page + 1;
        ?>" class="btn-admin btn-admin-outline text-sm"><?php 
        echo t('pagination_next');
        ?> &raquo;</a>
        <?php 
    } else {
        ?>
            <span></span>
        <?php 
    }
    ?>
    </div>
    <?php 
}
?>
</div>
<?php 
