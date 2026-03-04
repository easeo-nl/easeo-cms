<?php
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
$postsData = load_json('posts.json');
$posts = $postsData['posts'] ?? [];
$mediaData = load_json('media.json');
$media = $mediaData['files'] ?? [];
$users = get_users();

// Count submissions
$submissionFiles = glob(EASEO_DATA . '/submissions/*.json') ?: [];
$totalSubmissions = count($submissionFiles);
$unreadSubmissions = 0;
foreach ($submissionFiles as $f) {
    $s = json_decode(file_get_contents($f), true);
    if ($s && empty($s['gelezen'])) $unreadSubmissions++;
}

$recentActivity = read_audit_log(10);

// Check welcome screen
$showWelcome = false;
$siteRaw = json_decode(file_get_contents(EASEO_DATA . '/site.json'), true);
if (!empty($siteRaw['show_welcome'])) $showWelcome = true;
?>

<?php if ($showWelcome): ?>
<div id="welcome-screen" style="background:#1e293b;border:1px solid #334155;border-radius:12px;padding:40px;margin-bottom:32px;">

  <h2 style="color:#f8fafc;font-size:22px;font-weight:700;margin:0 0 8px 0;">
    Welkom bij je nieuwe website
  </h2>
  <p style="color:#94a3b8;font-size:14px;margin:0 0 32px 0;">
    Alles is klaar. Hier zijn de drie dingen die je het vaakst gaat doen:
  </p>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:32px;">

    <a href="/beheer/?tab=blog-edit" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#9997;&#65039;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Blogpost schrijven</div>
      <div style="color:#64748b;font-size:13px;">Schrijf een nieuw artikel voor je website.</div>
    </a>

    <a href="/beheer/?tab=content" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#128221;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Tekst aanpassen</div>
      <div style="color:#64748b;font-size:13px;">Wijzig teksten, titels en afbeeldingen op je pagina's.</div>
    </a>

    <a href="/beheer/?tab=media" style="display:block;background:#0f172a;border:1px solid #334155;border-radius:8px;padding:24px;text-decoration:none;transition:border-color 0.15s;">
      <div style="font-size:28px;margin-bottom:12px;">&#128248;</div>
      <div style="color:#f8fafc;font-size:15px;font-weight:600;margin-bottom:6px;">Afbeelding uploaden</div>
      <div style="color:#64748b;font-size:13px;">Voeg foto's toe aan je mediabibliotheek.</div>
    </a>

  </div>

  <button onclick="dismissWelcome()" style="background:#334155;color:#94a3b8;border:none;padding:10px 20px;border-radius:6px;font-size:13px;cursor:pointer;font-family:inherit;">
    Niet meer tonen
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
<?php endif; ?>

<h1 class="text-2xl font-bold text-white mb-6">Dashboard</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card">
        <div class="stat-value"><?= count($pages) ?></div>
        <div class="stat-label">Pagina's</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($posts) ?></div>
        <div class="stat-label">Blogposts</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= count($media) ?></div>
        <div class="stat-label">Media bestanden</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $unreadSubmissions ?><span class="text-sm font-normal text-gray-500"> / <?= $totalSubmissions ?></span></div>
        <div class="stat-label">Ongelezen berichten</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Quick actions -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Snelle acties</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="/beheer/?tab=content" class="btn-admin btn-admin-outline w-full justify-center">Content bewerken</a>
            <a href="/beheer/?tab=blog-edit" class="btn-admin btn-admin-outline w-full justify-center">Nieuw blogpost</a>
            <a href="/beheer/?tab=media" class="btn-admin btn-admin-outline w-full justify-center">Media uploaden</a>
            <a href="/beheer/?tab=inbox" class="btn-admin btn-admin-outline w-full justify-center">
                Inbox
                <?php if ($unreadSubmissions > 0): ?>
                <span class="badge badge-primary ml-1"><?= $unreadSubmissions ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="admin-card">
        <h2 class="text-lg font-semibold text-white mb-4">Recente activiteit</h2>
        <?php if (empty($recentActivity)): ?>
            <p class="text-gray-500 text-sm">Geen activiteit gevonden.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentActivity as $entry): ?>
                <div class="flex items-start gap-3 text-sm">
                    <span class="text-gray-500 shrink-0 w-32"><?= e($entry['datum'] ?? '') ?></span>
                    <span class="text-gray-300">
                        <strong class="text-white"><?= e($entry['gebruiker'] ?? '') ?></strong>
                        — <?= e($entry['actie'] ?? '') ?>
                        <?php if (!empty($entry['details'])): ?>
                        <span class="text-gray-500">(<?= e($entry['details']) ?>)</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Site info -->
<div class="admin-card mt-6">
    <h2 class="text-lg font-semibold text-white mb-4">Site informatie</h2>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
        <div>
            <span class="text-gray-500">Bedrijfsnaam:</span>
            <span class="text-white ml-1"><?= e(site('company.name', '—')) ?></span>
        </div>
        <div>
            <span class="text-gray-500">E-mail:</span>
            <span class="text-white ml-1"><?= e(site('company.email', '—')) ?></span>
        </div>
        <div>
            <span class="text-gray-500">PHP versie:</span>
            <span class="text-white ml-1"><?= phpversion() ?></span>
        </div>
    </div>
</div>
