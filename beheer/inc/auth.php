<?php
/**
 * EASEO CMS — Authentication, session management, CSRF
 */
require_once dirname(__DIR__, 2) . '/includes/content.php';
require_once dirname(__DIR__, 2) . '/includes/rate-limiter.php';
require_once dirname(__DIR__, 2) . '/includes/audit.php';

// Secure session config
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');
session_start();

// CSRF Token
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrf_token(), $token);
}

// Auth helpers
function get_users(): array {
    $data = load_json('users.json');
    return $data['users'] ?? [];
}

function save_users(array $users): bool {
    return save_json('users.json', ['users' => $users]);
}

function find_user(string $email): ?array {
    foreach (get_users() as $user) {
        if (strcasecmp($user['email'], $email) === 0) {
            return $user;
        }
    }
    return null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['easeo_admin']['email']);
}

function current_user(): ?array {
    return $_SESSION['easeo_admin'] ?? null;
}

function is_admin(): bool {
    return ($_SESSION['easeo_admin']['rol'] ?? '') === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /beheer/?tab=login');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Onvoldoende rechten.';
        header('Location: /beheer/');
        exit;
    }
}

// Login
function attempt_login(string $email, string $password): bool {
    $limiter = new RateLimiter(5, 900);

    if ($limiter->isLimited()) {
        $_SESSION['flash_error'] = 'Te veel inlogpogingen. Probeer het later opnieuw.';
        return false;
    }

    $user = find_user($email);

    if (!$user || !password_verify($password, $user['wachtwoord'])) {
        $limiter->hit();
        audit_log('login_mislukt', "E-mail: {$email}");
        $_SESSION['flash_error'] = 'Ongeldige inloggegevens.';
        return false;
    }

    // Regenerate session ID
    session_regenerate_id(true);

    $_SESSION['easeo_admin'] = [
        'email' => $user['email'],
        'naam' => $user['naam'],
        'rol' => $user['rol'],
    ];

    $limiter->reset();
    audit_log('login', "Gebruiker: {$user['naam']}");
    return true;
}

// Logout
function logout(): void {
    $name = $_SESSION['easeo_admin']['naam'] ?? 'onbekend';
    audit_log('logout', "Gebruiker: {$name}");

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// Flash messages
function flash_error(): string {
    $msg = $_SESSION['flash_error'] ?? '';
    unset($_SESSION['flash_error']);
    return $msg;
}

function flash_success(): string {
    $msg = $_SESSION['flash_success'] ?? '';
    unset($_SESSION['flash_success']);
    return $msg;
}

// Handle login/logout actions
if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    logout();
    header('Location: /beheer/?tab=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['tab'] ?? '') === 'login') {
    if (!verify_csrf()) {
        $_SESSION['flash_error'] = 'Ongeldig CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['wachtwoord'] ?? '';
        if (attempt_login($email, $password)) {
            header('Location: /beheer/');
            exit;
        }
    }
}
