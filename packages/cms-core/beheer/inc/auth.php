<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Mail\Mailer;
/**
 * EASEO CMS — Authentication, session management, CSRF, 2FA, account lockout
 */
require_once dirname(__DIR__, 2) . '/includes/rate-limiter.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';
// Secure session config
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();
// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 30 * 60);
if (is_logged_in_raw()) {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        $name = $_SESSION['easeo_admin']['naam'] ?? 'onbekend';
        audit_log('sessie_verlopen', "Gebruiker: {$name}");
        session_unset();
        session_destroy();
        session_start();
        header('Location: /beheer/?tab=login&timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}
// CSRF Token
function csrf_token() : string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() : string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf() : bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrf_token(), $token);
}
// Auth helpers
function get_users() : array
{
    $data = ContentRepository::loadJson('users.json');
    return $data['users'] ?? [];
}
function save_users(array $users) : bool
{
    return ContentRepository::saveJson('users.json', ['users' => $users]);
}
function find_user(string $email) : ?array
{
    foreach (get_users() as $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            return $user;
        }
    }
    return null;
}
function find_user_index(string $email) : int
{
    $users = get_users();
    foreach ($users as $idx => $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            return $idx;
        }
    }
    return -1;
}
function update_user_field(string $email, string $field, $value) : void
{
    $users = get_users();
    foreach ($users as $idx => $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            $users[$idx][$field] = $value;
            save_users($users);
            return;
        }
    }
}
function update_user_fields(string $email, array $fields) : void
{
    $users = get_users();
    foreach ($users as $idx => $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            foreach ($fields as $k => $v) {
                $users[$idx][$k] = $v;
            }
            save_users($users);
            return;
        }
    }
}
// Raw session check (before timeout logic runs)
function is_logged_in_raw() : bool
{
    return !empty($_SESSION['easeo_admin']['email']);
}
function is_logged_in() : bool
{
    return is_logged_in_raw();
}
function current_user() : ?array
{
    return $_SESSION['easeo_admin'] ?? null;
}
function is_admin() : bool
{
    return ($_SESSION['easeo_admin']['rol'] ?? '') === 'admin';
}
function require_login() : void
{
    if (!is_logged_in()) {
        header('Location: /beheer/?tab=login');
        exit;
    }
}
function require_admin() : void
{
    require_login();
    if (!is_admin()) {
        $_SESSION['flash_error'] = Translator::translate('error_insufficient_permissions');
        header('Location: /beheer/');
        exit;
    }
}
// Account lockout (10 failed attempts = 15 min lock)
function is_account_locked(array $user) : bool
{
    $lockedUntil = $user['locked_until'] ?? null;
    if (!$lockedUntil) {
        return false;
    }
    if (time() < (int) $lockedUntil) {
        return true;
    }
    // Lock expired, clear it
    update_user_fields($user['email'], ['locked_until' => null, 'failed_attempts' => 0]);
    return false;
}
function record_failed_attempt(string $email) : void
{
    $user = find_user($email);
    if (!$user) {
        return;
    }
    $attempts = (int) ($user['failed_attempts'] ?? 0) + 1;
    $fields = ['failed_attempts' => $attempts];
    if ($attempts >= 10) {
        $fields['locked_until'] = time() + 15 * 60;
        // 15 minutes
        audit_log('account_vergrendeld', "Account: {$email} na {$attempts} mislukte pogingen", 'systeem');
    }
    update_user_fields($email, $fields);
}
function clear_failed_attempts(string $email) : void
{
    update_user_fields($email, ['failed_attempts' => 0, 'locked_until' => null]);
}
function get_lock_remaining(array $user) : int
{
    $lockedUntil = (int) ($user['locked_until'] ?? 0);
    return max(0, (int) ceil(($lockedUntil - time()) / 60));
}
// 2FA helpers
function generate_2fa_code() : string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
function mask_email(string $email) : string
{
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return '***';
    }
    $local = $parts[0];
    $domain = $parts[1];
    $masked = $local[0] . str_repeat('*', max(1, strlen($local) - 1));
    return $masked . '@' . $domain;
}
function send_2fa_code(string $email, string $code) : bool
{
    require_once dirname(__DIR__, 2) . '/includes/mailer.php';
    $companyName = ContentRepository::siteValue('company.name', 'EASEO CMS');
    $subject = "Verificatiecode {$companyName} beheer";
    $body = "<h2>Verificatiecode</h2>" . "<p>Uw verificatiecode is: <strong style='font-size:24px;letter-spacing:4px'>{$code}</strong></p>" . "<p>Deze code is 10 minuten geldig.</p>" . "<p style='color:#888'>Als u niet heeft geprobeerd in te loggen, wijzig dan uw wachtwoord.</p>";
    $result = Mailer::send($email, $subject, $body);
    return $result === true;
}
function is_2fa_enabled(array $user) : bool
{
    return !empty($user['two_factor_enabled']);
}
// Login
function attempt_login(string $email, string $password) : bool|string
{
    $limiter = new RateLimiter(5, 900);
    if ($limiter->isLimited()) {
        $_SESSION['flash_error'] = Translator::translate('error_too_many_login_attempts');
        return false;
    }
    $user = find_user($email);
    if (!$user || !password_verify($password, $user['wachtwoord'])) {
        $limiter->hit();
        if ($user) {
            record_failed_attempt($email);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        audit_log('login_mislukt', "E-mail: {$email}, IP: {$ip}", 'anoniem');
        $_SESSION['flash_error'] = Translator::translate('error_invalid_credentials');
        return false;
    }
    // Check account lockout
    if (is_account_locked($user)) {
        $remaining = get_lock_remaining($user);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        audit_log('login_geblokkeerd', "Account vergrendeld: {$email}, IP: {$ip}", 'anoniem');
        $_SESSION['flash_error'] = Translator::translate('error_account_locked', ['minutes' => $remaining]);
        return false;
    }
    // Password correct — check 2FA
    if (is_2fa_enabled($user)) {
        $code = generate_2fa_code();
        update_user_fields($email, [
            'two_factor_code' => password_hash($code, PASSWORD_DEFAULT),
            'two_factor_expires' => time() + 600,
            // 10 minutes
            'two_factor_attempts' => 0,
        ]);
        $sent = send_2fa_code($email, $code);
        if (!$sent) {
            $_SESSION['flash_error'] = Translator::translate('error_2fa_send_failed');
            audit_log('2fa_code_mislukt', "E-mail versturen mislukt voor: {$email}");
            return false;
        }
        // Store pending login in session
        $_SESSION['2fa_pending'] = ['email' => $email, 'timestamp' => time()];
        $_SESSION['2fa_last_sent'] = time();
        audit_log('2fa_code_verstuurd', "Naar: " . mask_email($email));
        return '2fa';
        // Signal that 2FA is required
    }
    // No 2FA — complete login
    complete_login($user);
    return true;
}
function complete_login(array $user) : void
{
    session_regenerate_id(true);
    $_SESSION['easeo_admin'] = ['email' => $user['email'], 'naam' => $user['naam'], 'rol' => $user['rol']];
    $_SESSION['last_activity'] = time();
    clear_failed_attempts($user['email']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    audit_log('login', "Gebruiker: {$user['naam']}, IP: {$ip}");
}
function verify_2fa_code(string $inputCode) : bool
{
    $pending = $_SESSION['2fa_pending'] ?? null;
    if (!$pending) {
        return false;
    }
    $email = $pending['email'];
    $user = find_user($email);
    if (!$user) {
        return false;
    }
    // Check expiry
    if (time() > (int) ($user['two_factor_expires'] ?? 0)) {
        $_SESSION['flash_error'] = Translator::translate('error_2fa_code_expired');
        audit_log('2fa_code_verlopen', "Account: {$email}");
        return false;
    }
    // Check attempts
    $attempts = (int) ($user['two_factor_attempts'] ?? 0);
    if ($attempts >= 3) {
        $_SESSION['flash_error'] = Translator::translate('error_2fa_too_many_attempts');
        audit_log('2fa_te_veel_pogingen', "Account: {$email}");
        return false;
    }
    // Verify code
    if (!password_verify($inputCode, $user['two_factor_code'] ?? '')) {
        update_user_field($email, 'two_factor_attempts', $attempts + 1);
        audit_log('2fa_code_fout', "Account: {$email}, poging " . ($attempts + 1));
        $_SESSION['flash_error'] = Translator::translate('error_2fa_wrong_code', ['remaining' => 2 - $attempts]);
        return false;
    }
    // Code correct — clear 2FA data and complete login
    update_user_fields($email, ['two_factor_code' => null, 'two_factor_expires' => null, 'two_factor_attempts' => 0]);
    unset($_SESSION['2fa_pending']);
    complete_login($user);
    return true;
}
function resend_2fa_code() : bool
{
    $pending = $_SESSION['2fa_pending'] ?? null;
    if (!$pending) {
        return false;
    }
    // Rate limit: max 1 per 60 seconds
    $lastSent = $_SESSION['2fa_last_sent'] ?? 0;
    if (time() - $lastSent < 60) {
        $_SESSION['flash_error'] = Translator::translate('error_2fa_wait_seconds', ['seconds' => 60 - (time() - $lastSent)]);
        return false;
    }
    $email = $pending['email'];
    $user = find_user($email);
    if (!$user) {
        return false;
    }
    $code = generate_2fa_code();
    update_user_fields($email, ['two_factor_code' => password_hash($code, PASSWORD_DEFAULT), 'two_factor_expires' => time() + 600, 'two_factor_attempts' => 0]);
    $sent = send_2fa_code($email, $code);
    if ($sent) {
        $_SESSION['2fa_last_sent'] = time();
        audit_log('2fa_code_opnieuw_verstuurd', "Naar: " . mask_email($email));
        $_SESSION['flash_success'] = Translator::translate('success_2fa_code_resent');
        return true;
    }
    $_SESSION['flash_error'] = Translator::translate('error_2fa_code_not_sent');
    return false;
}
// Logout
function logout() : void
{
    $name = $_SESSION['easeo_admin']['naam'] ?? 'onbekend';
    audit_log('logout', "Gebruiker: {$name}");
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
// Flash messages
function flash_error() : string
{
    $msg = $_SESSION['flash_error'] ?? '';
    unset($_SESSION['flash_error']);
    return $msg;
}
function flash_success() : string
{
    $msg = $_SESSION['flash_success'] ?? '';
    unset($_SESSION['flash_success']);
    return $msg;
}
// Handle login/logout/2FA actions
if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    logout();
    header('Location: /beheer/?tab=login');
    exit;
}
// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_2fa'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $code = trim($_POST['2fa_code'] ?? '');
        if (verify_2fa_code($code)) {
            header('Location: /beheer/');
            exit;
        }
    }
}
// Handle 2FA resend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_2fa'])) {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        resend_2fa_code();
    }
}
// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['tab'] ?? '') === 'login') {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = Translator::translate('error_invalid_csrf');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['wachtwoord'] ?? '';
        $result = attempt_login($email, $password);
        if ($result === true) {
            header('Location: /beheer/');
            exit;
        } elseif ($result === '2fa') {
            // Show 2FA page — handled in index.php
        }
    }
}
