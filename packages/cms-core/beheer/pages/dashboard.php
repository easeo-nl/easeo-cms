<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — Dashboard
 */
// Handle dismiss welcome
if (isset($_GET['action']) && $_GET['action'] === 'dismiss_welcome' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_path = EASEO_DATA . '/site.json';
    $siteJson = json_decode(file_get_contents($site_path), true);
    $siteJson['show_welcome'] = false;
    file_put_contents($site_path, json_encode($siteJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    http_response_code(200);
    exit;
}
$pages = $content;
$postsData = ContentRepository::loadJson('posts.json');
$posts = $postsData['posts'] ?? [];
$mediaData = ContentRepository::loadJson('media.json');
$media = $mediaData['files'] ?? [];
$users = get_users();
// Count submissions
$submissionFiles = glob(EASEO_DATA . '/submissions/*.json') ?: [];
$totalSubmissions = count($submissionFiles);
$unreadSubmissions = 0;
foreach ($submissionFiles as $f) {
    $s = json_decode(file_get_contents($f), true);
    if ($s && empty($s['gelezen'])) {
        $unreadSubmissions++;
    }
}
$recentActivity = read_audit_log(10);
// Check welcome screen
$showWelcome = false;
$siteRaw = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true);
if (!empty($siteRaw['show_welcome'])) {
    $showWelcome = true;
}
?>

<?php 
if ($showWelcome) {
    ?>
<div id="welcome-screen" style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:40px;margin-bottom:32px;">

  <h2 style="color:#f8fafc;font-size:22px;font-weight:700;margin:0 0 8px 0;">
    <?php 
    echo Translator::translate('welcome_title');
    ?>
  </h2>
  <p style="color:#94a3b8;font-size:14px;margin:0 0 32px 0;">
    <?php 
    echo Translator::translate('welcome_subtitle');
    ?>
  </p>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;">

    <a href="/beheer/?tab=blog-edit" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#9997;&#65039;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;"><?php 
    echo Translator::translate('welcome_action_write_post');
    ?></div>
      <div style="color:#64748b;font-size:13px;"><?php 
    echo Translator::translate('welcome_action_write_post_desc');
    ?></div>
    </a>

    <a href="/beheer/?tab=content" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#128221;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;"><?php 
    echo Translator::translate('welcome_action_edit_content');
    ?></div>
      <div style="color:#64748b;font-size:13px;"><?php 
    echo Translator::translate('welcome_action_edit_content_desc');
    ?></div>
    </a>

    <a href="/beheer/?tab=media" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#128248;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;"><?php 
    echo Translator::translate('welcome_action_upload_image');
    ?></div>
      <div style="color:#64748b;font-size:13px;"><?php 
    echo Translator::translate('welcome_action_upload_image_desc');
    ?></div>
    </a>

  </div>

  <button onclick="dismissWelcome()" style="background:#334155;color:#94a3b8;border:none;padding:10px 20px;border-radius:6px;font-size:13px;cursor:pointer;font-family:inherit;">
    <?php 
    echo Translator::translate('welcome_dismiss_button');
    ?>
  </button>

</div>

<script>
function dismissWelcome() {
  fetch('/beheer/?tab=dashboard&action=dismiss_welcome', { method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'} })
    .then(function() {
      document.getElementById('welcome-screen').style.display = 'none';
    });
}
</script>
<?php 
}
?>

<h1 class="text-2xl font-bold text-white mb-6"><?php 
echo Translator::translate('dashboard_title');
?></h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <div class="stat-value"><?php 
echo count($pages);
?></div>
        <div class="stat-label"><?php 
echo Translator::translate('stat_pages');
?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php 
echo count($posts);
?></div>
        <div class="stat-label"><?php 
echo Translator::translate('stat_blogposts');
?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php 
echo count($media);
?></div>
        <div class="stat-label"><?php 
echo Translator::translate('stat_media_files');
?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php 
echo $unreadSubmissions;
?><span class="text-sm font-normal text-gray-500"> / <?php 
echo $totalSubmissions;
?></span></div>
        <div class="stat-label"><?php 
echo Translator::translate('stat_unread_messages');
?></div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Quick actions -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('dashboard_quick_actions');
?></h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="/beheer/?tab=content" class="btn-admin btn-admin-outline w-full justify-center"><?php 
echo Translator::translate('quick_action_edit_content');
?></a>
            <a href="/beheer/?tab=blog-edit" class="btn-admin btn-admin-outline w-full justify-center"><?php 
echo Translator::translate('quick_action_new_post');
?></a>
            <a href="/beheer/?tab=media" class="btn-admin btn-admin-outline w-full justify-center"><?php 
echo Translator::translate('quick_action_upload_media');
?></a>
            <a href="/beheer/?tab=inbox" class="btn-admin btn-admin-outline w-full justify-center">
                <?php 
echo Translator::translate('quick_action_inbox');
?>
                <?php 
if ($unreadSubmissions > 0) {
    ?>
                <span class="badge badge-primary ml-1"><?php 
    echo $unreadSubmissions;
    ?></span>
                <?php 
}
?>
            </a>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('dashboard_recent_activity');
?></h2>
        <?php 
if (empty($recentActivity)) {
    ?>
            <p class="text-gray-500 text-sm"><?php 
    echo Translator::translate('no_activity_found');
    ?></p>
        <?php 
} else {
    ?>
            <div class="space-y-3">
                <?php 
    foreach ($recentActivity as $entry) {
        ?>
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-gray-500 shrink-0 w-32"><?php 
        echo ContentRepository::escape($entry['datum'] ?? '');
        ?></span>
                    <span class="text-gray-300">
                        <strong class="text-white"><?php 
        echo ContentRepository::escape($entry['gebruiker'] ?? '');
        ?></strong>
                        — <?php 
        echo ContentRepository::escape($entry['actie'] ?? '');
        ?>
                        <?php 
        if (!empty($entry['details'])) {
            ?>
                        <span class="text-gray-500">(<?php 
            echo ContentRepository::escape($entry['details']);
            ?>)</span>
                        <?php 
        }
        ?>
                    </span>
                </div>
                <?php 
    }
    ?>
            </div>
        <?php 
}
?>
    </div>
</div>

<!-- Site info -->
<div class="admin-card mt-6">
    <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo Translator::translate('dashboard_site_info');
?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <span class="text-gray-500"><?php 
echo Translator::translate('site_info_company_name');
?></span>
            <span class="text-white ml-1"><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.name', '—'));
?></span>
        </div>
        <div>
            <span class="text-gray-500"><?php 
echo Translator::translate('site_info_email');
?></span>
            <span class="text-white ml-1"><?php 
echo ContentRepository::escape(ContentRepository::siteValue('company.email', '—'));
?></span>
        </div>
        <div>
            <span class="text-gray-500"><?php 
echo Translator::translate('site_info_php_version');
?></span>
            <span class="text-white ml-1"><?php 
echo phpversion();
?></span>
        </div>
    </div>
</div>
<?php 
