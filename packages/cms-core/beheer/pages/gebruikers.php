<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
/**
 * EASEO CMS — User management (admin only)
 */
$users = get_users();
// Handle create/edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $naam = trim($_POST['naam'] ?? '');
        $rol = in_array($_POST['rol'] ?? '', ['admin', 'redacteur']) ? $_POST['rol'] : 'redacteur';
        $password = $_POST['wachtwoord'] ?? '';
        $editIndex = $_POST['edit_index'] ?? '';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = Translator::translate('error_invalid_email');
        } elseif (empty($naam)) {
            $_SESSION['flash_error'] = Translator::translate('error_name_required');
        } else {
            if ($editIndex !== '') {
                // Edit existing
                $idx = (int) $editIndex;
                if (isset($users[$idx])) {
                    $users[$idx]['email'] = $email;
                    $users[$idx]['naam'] = $naam;
                    $users[$idx]['rol'] = $rol;
                    if (!empty($password)) {
                        $users[$idx]['wachtwoord'] = password_hash($password, PASSWORD_DEFAULT);
                        audit_log('wachtwoord_gewijzigd', "Gebruiker: {$naam}");
                        session_regenerate_id(true);
                    }
                    save_users($users);
                    audit_log('gebruiker_bewerkt', "Gebruiker: {$naam}");
                    $_SESSION['flash_success'] = Translator::translate('success_user_updated');
                }
            } else {
                // Check duplicate email
                $exists = false;
                foreach ($users as $u) {
                    if (strcasecmp($u['email'], $email) === 0) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) {
                    $_SESSION['flash_error'] = Translator::translate('error_email_in_use');
                } elseif (empty($password)) {
                    $_SESSION['flash_error'] = Translator::translate('error_password_required');
                } else {
                    $users[] = ['email' => $email, 'naam' => $naam, 'rol' => $rol, 'wachtwoord' => password_hash($password, PASSWORD_DEFAULT), 'aangemaakt' => date('Y-m-d H:i:s')];
                    save_users($users);
                    audit_log('gebruiker_aangemaakt', "Gebruiker: {$naam}");
                    $_SESSION['flash_success'] = Translator::translate('success_user_created');
                }
            }
        }
    }
    header('Location: /beheer/?tab=gebruikers');
    exit;
}
// Handle 2FA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $idx = (int) ($_POST['toggle_index'] ?? -1);
        if (isset($users[$idx])) {
            $current = !empty($users[$idx]['two_factor_enabled']);
            $smtpEnabled = !empty(ContentRepository::siteValue('smtp.enabled'));
            if (!$current && !$smtpEnabled) {
                $_SESSION['flash_error'] = Translator::translate('error_2fa_requires_smtp');
            } else {
                $users[$idx]['two_factor_enabled'] = !$current;
                save_users($users);
                $status = !$current ? 'ingeschakeld' : 'uitgeschakeld';
                audit_log('2fa_' . $status, "Gebruiker: {$users[$idx]['naam']}");
                $_SESSION['flash_success'] = "2FA {$status} voor {$users[$idx]['naam']}.";
                session_regenerate_id(true);
            }
        }
    }
    header('Location: /beheer/?tab=gebruikers');
    exit;
}
// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $idx = (int) ($_POST['delete_index'] ?? -1);
        if (isset($users[$idx])) {
            // Don't allow deleting yourself
            if (strcasecmp($users[$idx]['email'], current_user()['email']) === 0) {
                $_SESSION['flash_error'] = Translator::translate('error_cannot_delete_self');
            } else {
                $naam = $users[$idx]['naam'];
                array_splice($users, $idx, 1);
                save_users($users);
                audit_log('gebruiker_verwijderd', "Gebruiker: {$naam}");
                $_SESSION['flash_success'] = Translator::translate('success_user_deleted');
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
if (isset($_GET['edit']) && isset($users[(int) $_GET['edit']])) {
    $editIndex = (int) $_GET['edit'];
    $editUser = $users[$editIndex];
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-white"><?php 
echo Translator::translate('users_title');
?></h1>
</div>

<!-- User form -->
<div class="admin-card mb-6">
    <h2 class="text-lg font-semibold text-white mb-4"><?php 
echo $editUser ? Translator::translate('user_edit_heading') : Translator::translate('user_new_heading');
?></h2>
    <form method="POST">
        <?php 
echo csrf_field();
?>
        <?php 
if ($editUser) {
    ?>
        <input type="hidden" name="edit_index" value="<?php 
    echo $editIndex;
    ?>">
        <?php 
}
?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_name');
?></label>
                <input type="text" name="naam" value="<?php 
echo ContentRepository::escape($editUser['naam'] ?? '');
?>" required class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_email_address');
?></label>
                <input type="email" name="email" value="<?php 
echo ContentRepository::escape($editUser['email'] ?? '');
?>" required class="admin-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_password');
?> <?php 
echo $editUser ? Translator::translate('hint_password_leave_blank') : '';
?></label>
                <input type="password" name="wachtwoord" <?php 
echo $editUser ? '' : 'required';
?> class="admin-input w-full" minlength="8">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1"><?php 
echo Translator::translate('field_label_role');
?> <span class="help-tooltip" data-help="<?php 
echo Translator::translate('tooltip_user_role');
?>">?</span></label>
                <select name="rol" class="admin-input w-full">
                    <option value="admin" <?php 
echo ($editUser['rol'] ?? '') === 'admin' ? 'selected' : '';
?>><?php 
echo Translator::translate('role_admin');
?></option>
                    <option value="redacteur" <?php 
echo ($editUser['rol'] ?? 'redacteur') === 'redacteur' ? 'selected' : '';
?>><?php 
echo Translator::translate('role_editor');
?></option>
                </select>
            </div>
        </div>

        <div class="flex gap-2 mt-4">
            <button type="submit" name="save_user" class="btn-admin btn-admin-primary">
                <?php 
echo $editUser ? Translator::translate('button_update') : Translator::translate('button_create');
?>
            </button>
            <?php 
if ($editUser) {
    ?>
            <a href="/beheer/?tab=gebruikers" class="btn-admin btn-admin-outline"><?php 
    echo Translator::translate('button_cancel');
    ?></a>
            <?php 
}
?>
        </div>
    </form>
</div>

<!-- User list -->
<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th><?php 
echo Translator::translate('field_label_name');
?></th>
                <th><?php 
echo Translator::translate('table_header_email');
?></th>
                <th><?php 
echo Translator::translate('table_header_role');
?></th>
                <th><?php 
echo Translator::translate('table_header_2fa');
?></th>
                <th><?php 
echo Translator::translate('table_header_created');
?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php 
foreach ($users as $idx => $user) {
    ?>
            <tr>
                <td class="text-white"><?php 
    echo ContentRepository::escape($user['naam'] ?? '');
    ?></td>
                <td><?php 
    echo ContentRepository::escape($user['email'] ?? '');
    ?></td>
                <td><span class="badge <?php 
    echo $user['rol'] === 'admin' ? 'badge-primary' : 'badge-muted';
    ?>"><?php 
    echo ContentRepository::escape($user['rol'] ?? '');
    ?></span></td>
                <td>
                    <form method="POST" class="inline">
                        <?php 
    echo csrf_field();
    ?>
                        <input type="hidden" name="toggle_index" value="<?php 
    echo $idx;
    ?>">
                        <?php 
    $is2fa = !empty($user['two_factor_enabled']);
    $smtpOn = !empty(ContentRepository::siteValue('smtp.enabled'));
    $canToggle = $is2fa || $smtpOn;
    ?>
                        <button type="submit" name="toggle_2fa"
                            class="text-xs px-2 py-0.5 rounded <?php 
    echo $is2fa ? 'bg-green-900/50 text-green-400' : 'bg-gray-700 text-gray-400';
    ?>"
                            <?php 
    echo !$canToggle ? 'disabled title="' . Translator::translate('tooltip_2fa_smtp_required') . '"' : '';
    ?>>
                            <?php 
    echo $is2fa ? Translator::translate('toggle_2fa_on') : Translator::translate('toggle_2fa_off');
    ?>
                        </button>
                    </form>
                </td>
                <td class="text-gray-500"><?php 
    echo ContentRepository::escape($user['aangemaakt'] ?? '');
    ?></td>
                <td class="text-right">
                    <a href="/beheer/?tab=gebruikers&edit=<?php 
    echo $idx;
    ?>" class="text-blue-400 hover:text-blue-300 text-sm mr-2"><?php 
    echo Translator::translate('action_edit');
    ?></a>
                    <form method="POST" class="inline" onsubmit="return confirm('<?php 
    echo Translator::translate('confirm_delete_user');
    ?>')">
                        <?php 
    echo csrf_field();
    ?>
                        <input type="hidden" name="delete_index" value="<?php 
    echo $idx;
    ?>">
                        <button type="submit" name="delete_user" class="text-red-400 hover:text-red-300 text-sm"><?php 
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
</div>
<?php 
