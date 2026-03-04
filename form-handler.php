<?php
/**
 * EASEO CMS — Form POST handler
 */
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/form-engine.php';
require_once __DIR__ . '/includes/rate-limiter.php';
require_once __DIR__ . '/includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$formId = $_POST['form_id'] ?? '';
$form = get_form($formId);

if (!$form) {
    http_response_code(400);
    exit('Formulier niet gevonden.');
}

// CSRF check
if (!verify_csrf_frontend()) {
    $_SESSION['form_error'] = 'Beveiligingsfout. Probeer het opnieuw.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// Rate limiting
$limiter = new RateLimiter(10, 300);
if ($limiter->isLimited()) {
    $_SESSION['form_error'] = 'Te veel verzoeken. Probeer het later opnieuw.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// Honeypot check
if (!empty($_POST['website_url'])) {
    // Bot detected — silently redirect
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

// Collect and validate form data
$fields = $form['velden'] ?? [];
$data = [];
$errors = [];

foreach ($fields as $field) {
    $name = $field['naam'] ?? '';
    $value = trim($_POST[$name] ?? '');

    if (!empty($field['verplicht']) && $value === '') {
        $errors[] = ($field['label'] ?? $name) . ' is verplicht.';
    }

    // Basic type validation
    if ($value !== '' && ($field['type'] ?? 'text') === 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Ongeldig e-mailadres.';
        }
    }

    $data[$name] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if (!empty($errors)) {
    $_SESSION['form_error'] = implode(' ', $errors);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}

$limiter->hit();

// Save submission
$submission = [
    'id' => substr(md5(uniqid(mt_rand(), true)), 0, 12),
    'formulier_id' => $formId,
    'formulier_naam' => $form['naam'] ?? $formId,
    'data' => $data,
    'datum' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    'gelezen' => false,
];

$subDir = EASEO_DATA . '/submissions';
if (!is_dir($subDir)) mkdir($subDir, 0755, true);
file_put_contents($subDir . '/' . $submission['id'] . '.json',
    json_encode($submission, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

audit_log('formulier_verzonden', "Formulier: {$form['naam']}", 'bezoeker');

// Send email notification
$emailTo = $form['email_naar'] ?? site('company.email');
if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
    $subject = 'Nieuw bericht via ' . ($form['naam'] ?? 'formulier') . ' — ' . site('company.name', 'EASEO');
    $body = "Nieuw formulier inzending:\n\n";
    foreach ($data as $key => $value) {
        $body .= ucfirst($key) . ": " . $value . "\n";
    }
    $body .= "\nDatum: " . $submission['datum'] . "\n";
    $body .= "IP: " . $submission['ip'] . "\n";

    $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "Reply-To: " . ($data['email'] ?? $emailTo) . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($emailTo, $subject, $body, $headers);
}

// Regenerate CSRF token
$_SESSION['csrf_frontend'] = bin2hex(random_bytes(32));

// Success message
$_SESSION['form_success'] = $form['bevestiging'] ?? 'Bedankt voor uw bericht.';
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
