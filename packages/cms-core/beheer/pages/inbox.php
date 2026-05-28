<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
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
            $_SESSION['flash_success'] = Translator::translate('success_message_deleted');
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
    if ($sub) {
        $submissions[] = $sub;
    }
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
    <h1 class="text-2xl font-bold text-white"><?php 
echo Translator::translate('inbox_title');
?></h1>
    <span class="text-sm text-gray-500"><?php 
echo count($submissions);
?> <?php 
echo Translator::translate('inbox_messages_count_unit');
?></span>
</div>

<?php 
if ($viewSub) {
    ?>
<!-- Single submission view -->
<div class="admin-card mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg font-semibold text-white"><?php 
    echo ContentRepository::escape($viewSub['formulier_naam'] ?? '');
    ?></h2>
            <p class="text-sm text-gray-500"><?php 
    echo ContentRepository::escape($viewSub['datum'] ?? '');
    ?> — IP: <?php 
    echo ContentRepository::escape($viewSub['ip'] ?? '');
    ?></p>
        </div>
        <a href="/beheer/?tab=inbox" class="btn-admin btn-admin-outline text-sm">&larr; <?php 
    echo Translator::translate('button_back');
    ?></a>
    </div>

    <div class="space-y-3">
        <?php 
    foreach ($viewSub['data'] ?? [] as $key => $value) {
        ?>
        <div>
            <span class="text-sm text-gray-500"><?php 
        echo ContentRepository::escape(ucfirst($key));
        ?>:</span>
            <div class="text-white mt-0.5"><?php 
        echo nl2br(ContentRepository::escape($value));
        ?></div>
        </div>
        <?php 
    }
    ?>
    </div>

    <div class="flex gap-2 mt-6 pt-4 border-t border-gray-700">
        <?php 
    if (!empty($viewSub['data']['email'])) {
        ?>
        <a href="mailto:<?php 
        echo ContentRepository::escape($viewSub['data']['email']);
        ?>" class="btn-admin btn-admin-primary text-sm"><?php 
        echo Translator::translate('button_reply');
        ?></a>
        <?php 
    }
    ?>
        <form method="POST" class="inline" onsubmit="return confirm('<?php 
    echo Translator::translate('confirm_delete');
    ?>')">
            <?php 
    echo csrf_field();
    ?>
            <input type="hidden" name="submission_id" value="<?php 
    echo ContentRepository::escape($viewSub['id'] ?? '');
    ?>">
            <button type="submit" name="delete_submission" class="btn-admin btn-admin-danger text-sm"><?php 
    echo Translator::translate('action_delete');
    ?></button>
        </form>
    </div>
</div>

<?php 
} else {
    ?>
<!-- Submission list -->
<div class="admin-card">
    <?php 
    if (empty($submissions)) {
        ?>
        <p class="text-gray-500"><?php 
        echo Translator::translate('inbox_no_messages');
        ?></p>
    <?php 
    } else {
        ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width:20px"></th>
                <th><?php 
        echo Translator::translate('table_header_form');
        ?></th>
                <th><?php 
        echo Translator::translate('table_header_sender');
        ?></th>
                <th><?php 
        echo Translator::translate('table_header_date');
        ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php 
        foreach ($submissions as $sub) {
            ?>
            <tr class="<?php 
            echo empty($sub['gelezen']) ? 'font-semibold' : '';
            ?>">
                <td><?php 
            echo empty($sub['gelezen']) ? '<span class="unread-dot"></span>' : '';
            ?></td>
                <td>
                    <a href="/beheer/?tab=inbox&view=<?php 
            echo ContentRepository::escape($sub['id']);
            ?>" class="text-white hover:text-blue-400">
                        <?php 
            echo ContentRepository::escape($sub['formulier_naam'] ?? Translator::translate('unknown_form_name'));
            ?>
                    </a>
                </td>
                <td class="text-gray-400">
                    <?php 
            echo ContentRepository::escape($sub['data']['naam'] ?? $sub['data']['email'] ?? '—');
            ?>
                </td>
                <td class="text-gray-500"><?php 
            echo ContentRepository::escape($sub['datum'] ?? '');
            ?></td>
                <td class="text-right">
                    <form method="POST" class="inline" onsubmit="return confirm('<?php 
            echo Translator::translate('confirm_delete');
            ?>')">
                        <?php 
            echo csrf_field();
            ?>
                        <input type="hidden" name="submission_id" value="<?php 
            echo ContentRepository::escape($sub['id']);
            ?>">
                        <button type="submit" name="delete_submission" class="text-red-400 hover:text-red-300 text-sm"><?php 
            echo Translator::translate('action_delete');
            ?></button>
                    </form>
                </td>
            </tr>
            <?php 
        }
        ?>
        </tbody>
    </table>
    <?php 
    }
    ?>
</div>
<?php 
}
