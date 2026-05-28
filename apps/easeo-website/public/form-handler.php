<?php
use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;
use Easeo\Cms\Mail\Mailer;
use Easeo\Cms\Form\FormEngine;
use Easeo\Cms\Audit\AuditLogger;
use Easeo\Cms\Security\RateLimiter;
/**
 * EASEO CMS — Form POST handler
 */
require_once __DIR__ . '/../vendor/autoload.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}
$formId = $_POST['form_id'] ?? '';
$form = FormEngine::getForm($formId);
if (!$form) {
    http_response_code(400);
    exit(Translator::translate('error_form_not_found'));
}
// CSRF check
if (!FormEngine::verifyCsrf()) {
    $_SESSION['form_error'] = Translator::translate('error_security_csrf');
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    exit;
}
// Rate limiting
$limiter = new RateLimiter(10, 300);
if ($limiter->isLimited()) {
    $_SESSION['form_error'] = Translator::translate('error_too_many_requests');
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
        $errors[] = Translator::translate('error_field_required', ['field' => $field['label'] ?? $name]);
    }
    // Basic type validation
    if ($value !== '' && ($field['type'] ?? 'text') === 'email') {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[] = Translator::translate('error_invalid_email');
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
$submission = ['id' => substr(md5(uniqid(mt_rand(), true)), 0, 12), 'formulier_id' => $formId, 'formulier_naam' => $form['naam'] ?? $formId, 'data' => $data, 'datum' => date('Y-m-d H:i:s'), 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'gelezen' => false];
$subDir = EASEO_DATA . '/submissions';
if (!is_dir($subDir)) {
    mkdir($subDir, 0755, true);
}
file_put_contents($subDir . '/' . $submission['id'] . '.json', json_encode($submission, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
AuditLogger::log('formulier_verzonden', "Formulier: {$form['naam']}", 'bezoeker');
// Send email notification
$emailTo = $form['email_naar'] ?? ContentRepository::siteValue('company.email');
if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
    $subject = Translator::translate('email_subject_new_submission', ['form' => $form['naam'] ?? 'formulier']) . ' — ' . ContentRepository::siteValue('company.name', 'EASEO');
    $body = '<h2>' . Translator::translate('email_body_heading') . '</h2>';
    $body .= '<table style="border-collapse:collapse;width:100%">';
    foreach ($data as $key => $value) {
        $body .= '<tr><td style="padding:6px 12px;border:1px solid #ddd;font-weight:bold">' . ContentRepository::escape(ucfirst($key)) . '</td>';
        $body .= '<td style="padding:6px 12px;border:1px solid #ddd">' . ContentRepository::escape($value) . '</td></tr>';
    }
    $body .= '</table>';
    $body .= '<p style="color:#888;font-size:12px">' . Translator::translate('email_body_date_label') . ' ' . ContentRepository::escape($submission['datum']) . ' — ' . Translator::translate('email_body_ip_label') . ' ' . ContentRepository::escape($submission['ip']) . '</p>';
    $replyTo = $data['email'] ?? '';
    Mailer::send($emailTo, $subject, $body, $replyTo);
}
// Regenerate CSRF token
$_SESSION['csrf_frontend'] = bin2hex(random_bytes(32));
// Success message
$_SESSION['form_success'] = $form['bevestiging'] ?? Translator::translate('default_confirmation_message');
// Bewaar context voor dataLayer push op de vervolgpagina.
// `form_type` pakt het eerste select-veld — in een generieke CMS is dat de
// meest plausibele categorisering (bv. onderwerp, interesse, type project).
$formType = '';
foreach ($fields as $field) {
    if (($field['type'] ?? '') === 'select') {
        $formType = $data[$field['naam'] ?? ''] ?? '';
        break;
    }
}
$_SESSION['form_success_data'] = ['form_id' => $formId, 'form_name' => $form['naam'] ?? $formId, 'form_type' => $formType];
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
