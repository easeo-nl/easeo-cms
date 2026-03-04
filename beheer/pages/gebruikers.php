<?php
/**
 * EASEO CMS — User management (admin only)
 */
$users = get_users();

// Handle create/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $naam = trim($_POST['naam'] ?? '');
        $rol = in_array($_POST['rol'] ?? '', ['admin', 'redacteur']) ? $_POST['rol'] : 'redacteur';
        $password = $_POST['wachtwoord'] ?? '';
        $editIndex = $_POST['edit_index'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Ongeldig e-mailadres.';
        } elseif (empty($naam)) {
            $_SESSION['flash_error'] = 'Naam is verplicht.';
        } else {
            if ($editIndex !== '') {
                // Edit existing
                $idx = (int)$editIndex;
                if (isset($users[$idx])) {
                    $users[$idx]['email'] = $email;
                    $users[$idx]['naam'] = $naam;
                    $users[$idx]['rol'] = $rol;
                    if (!empty($password)) {
                        $users[$idx]['wachtwoord'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    save_users($users);
                    audit_log('gebruiker_bewerkt', "Gebruiker: {$naam}");
                    $_SESSION['flash_success'] = 'Gebruiker bijgewerkt.';
                }
            } else {
                // Check duplicate email
                $exists = false;
                foreach ($users as $u) {
                    if (strcasecmp($u['email'], $email) === 0) { $exists = true; break; }
                }

                if ($exists) {
                    $_SESSION['flash_error'] = 'E-mailadres is al in gebruik.';
                } elseif (empty($password)) {
                    $_SESSION['flash_error'] = 'Wachtwoord is verplicht voor nieuwe gebruikers.';
                } else {
                    $users[] = [
                        'email' => $email,
                        'naam' => $naam,
                        'rol' => $rol,
                        'wachtwoord' => password_hash($password, PASSWORD_DEFAULT),
                        'aangemaakt' => date('Y-m-d H:i:s'),
                    ];
                    save_users($users);
                    audit_log('gebruiker_aangemaakt', "Gebruiker: {$naam}");
                    $_SESSION['flash_success'] = 'Gebruiker aangemaakt.';
                }
            }
        }
    }
    header('Location: /beheer/?tab=gebruikers');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $idx = (int)($_POST['delete_index'] ?? -1);
        if (isset($users[$idx])) {
            // Don't allow deleting yourself
            if (strcasecmp($users[$idx]['email'], current_user()['email']) === 0) {
                $_SESSION['flash_error'] = 'U kunt uzelf niet verwijderen.';
            } else {
                $naam = $users[$idx]['naam'];
                array_splice($users, $idx, 1);
                save_users($users);
                audit_log('gebruiker_verwijderd', "Gebruiker: {$naam}");
                $_SESSION['flash_success'] = 'Gebruiker verwijderd.';
            }
        }
    }
    header('Location: /beheer/?tab=gebruikers');
    exit;
}

// Reload
$users = get_users();
$editUser = null;
$editIndex = '';
if (isset($_GET['edit']) && isset($users[(int)$_GET['edit']])) {
    $editIndex = (int)$_GET['edit'];
    $editUser = $users[$editIndex];
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white">Gebruikers</h1>
</div>

<!-- User form -->
<div class="admin-card mb-6">
    <h2 class="text-lg font-semibold text-white mb-4"><?= $editUser ? 'Gebruiker bewerken' : 'Nieuwe gebruiker' ?></h2>
    <form method="POST">
        <?= csrf_field() ?>
        <?php if ($editUser): ?>
        <input type="hidden" name="edit_index" value="<?= $editIndex ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Naam</label>
                <input type="text" name="naam" value="<?= e($editUser['naam'] ?? '') ?>" required class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">E-mailadres</label>
                <input type="email" name="email" value="<?= e($editUser['email'] ?? '') ?>" required class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Wachtwoord <?= $editUser ? '(laat leeg om niet te wijzigen)' : '' ?></label>
                <input type="password" name="wachtwoord" <?= $editUser ? '' : 'required' ?> class="admin-input w-full" minlength="8">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Rol <span class="help-tooltip" data-help="Beheerder heeft volledige toegang. Redacteur kan content en blog beheren maar geen instellingen wijzigen.">?</span></label>
                <select name="rol" class="admin-input w-full">
                    <option value="admin" <?= ($editUser['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="redacteur" <?= ($editUser['rol'] ?? 'redacteur') === 'redacteur' ? 'selected' : '' ?>>Redacteur</option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" name="save_user" class="btn-admin btn-admin-primary">
                <?= $editUser ? 'Bijwerken' : 'Aanmaken' ?>
            </button>
            <?php if ($editUser): ?>
            <a href="/beheer/?tab=gebruikers" class="btn-admin btn-admin-outline">Annuleren</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- User list -->
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Naam</th>
                <th>E-mail</th>
                <th>Rol</th>
                <th>Aangemaakt</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $idx => $user): ?>
            <tr>
                <td class="text-white"><?= e($user['naam'] ?? '') ?></td>
                <td><?= e($user['email'] ?? '') ?></td>
                <td><span class="badge <?= $user['rol'] === 'admin' ? 'badge-primary' : 'badge-muted' ?>"><?= e($user['rol'] ?? '') ?></span></td>
                <td class="text-gray-500"><?= e($user['aangemaakt'] ?? '') ?></td>
                <td class="text-right">
                    <a href="/beheer/?tab=gebruikers&edit=<?= $idx ?>" class="text-blue-400 hover:text-blue-300 text-sm mr-2">Bewerken</a>
                    <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze gebruiker wilt verwijderen?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete_index" value="<?= $idx ?>">
                        <button type="submit" name="delete_user" class="text-red-400 hover:text-red-300 text-sm">Verwijderen</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
