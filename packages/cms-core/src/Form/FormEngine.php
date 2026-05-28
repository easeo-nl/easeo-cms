<?php
declare(strict_types=1);

namespace Easeo\Cms\Form;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;

final class FormEngine
{
    public static function getFormsData(): array
    {
        return ContentRepository::loadJson('forms.json');
    }

    public static function getForms(): array
    {
        $data = self::getFormsData();
        return $data['forms'] ?? [];
    }

    public static function saveForms(array $forms): bool
    {
        return ContentRepository::saveJson('forms.json', ['forms' => $forms]);
    }

    public static function getForm(string $id): ?array
    {
        foreach (self::getForms() as $form) {
            if (($form['id'] ?? '') === $id) {
                return $form;
            }
        }
        return null;
    }

    public static function render(string $id, bool $showTitle = false): string
    {
        $form = self::getForm($id);
        if (!$form) {
            return '<p class="text-muted">' . Translator::translate('error_form_not_found') . '</p>';
        }
        $fields = $form['velden'] ?? [];
        $buttonText = $form['knop_tekst'] ?? 'Versturen';
        $success = $_SESSION['form_success'] ?? '';
        $error = $_SESSION['form_error'] ?? '';
        $successData = $_SESSION['form_success_data'] ?? null;
        unset($_SESSION['form_success'], $_SESSION['form_error'], $_SESSION['form_success_data']);
        $html = '';
        if ($success) {
            $html .= '<div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg mb-4">' . ContentRepository::escape($success) . '</div>';
            if ($successData && ($successData['form_id'] ?? '') === $id) {
                $payload = json_encode(['event' => 'formulier_verstuurd', 'form_id' => $successData['form_id'], 'form_name' => $successData['form_name'] ?? '', 'form_type' => $successData['form_type'] ?? ''], JSON_UNESCAPED_UNICODE);
                $html .= '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(' . $payload . ');</script>' . "\n";
            }
        }
        if ($error) {
            $html .= '<div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg mb-4">' . ContentRepository::escape($error) . '</div>';
        }
        $html .= '<form method="POST" action="/form-handler.php" class="space-y-4">' . "\n";
        $html .= '  <input type="hidden" name="form_id" value="' . ContentRepository::escape($id) . '">' . "\n";
        $html .= '  <input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">' . "\n";
        // Honeypot
        $html .= '  <div style="display:none"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>' . "\n";
        if ($showTitle && !empty($form['naam'])) {
            $html .= '  <h3 class="text-xl font-display font-bold text-dark mb-4">' . ContentRepository::escape($form['naam']) . '</h3>' . "\n";
        }
        foreach ($fields as $field) {
            $name = ContentRepository::escape($field['naam'] ?? '');
            $label = ContentRepository::escape($field['label'] ?? '');
            $type = $field['type'] ?? 'text';
            $required = !empty($field['verplicht']);
            $reqAttr = $required ? ' required' : '';
            $reqMark = $required ? ' <span class="text-red-500">*</span>' : '';
            $html .= '  <div>' . "\n";
            $html .= '    <label for="field-' . $name . '" class="block text-sm font-medium text-dark mb-1">' . $label . $reqMark . '</label>' . "\n";
            switch ($type) {
                case 'textarea':
                    $html .= '    <textarea id="field-' . $name . '" name="' . $name . '" rows="4"' . $reqAttr . ' class="w-full border border-gray-300 rounded-md p-2.5 focus:ring-primary focus:border-primary"></textarea>' . "\n";
                    break;
                case 'select':
                    $options = $field['opties'] ?? [];
                    $html .= '    <select id="field-' . $name . '" name="' . $name . '"' . $reqAttr . ' class="w-full border border-gray-300 rounded-md p-2.5">' . "\n";
                    $html .= '      <option value="">' . Translator::translate('form_select_placeholder') . '</option>' . "\n";
                    foreach ($options as $opt) {
                        $html .= '      <option value="' . ContentRepository::escape($opt) . '">' . ContentRepository::escape($opt) . '</option>' . "\n";
                    }
                    $html .= '    </select>' . "\n";
                    break;
                case 'checkbox':
                    $html .= '    <label class="flex items-center gap-2"><input type="checkbox" id="field-' . $name . '" name="' . $name . '" value="1"' . $reqAttr . '> ' . $label . '</label>' . "\n";
                    break;
                default:
                    $html .= '    <input type="' . ContentRepository::escape($type) . '" id="field-' . $name . '" name="' . $name . '"' . $reqAttr . ' class="w-full border border-gray-300 rounded-md p-2.5 focus:ring-primary focus:border-primary">' . "\n";
            }
            $html .= '  </div>' . "\n";
        }
        $html .= '  <button type="submit" class="btn btn-primary">' . ContentRepository::escape($buttonText) . '</button>' . "\n";
        $html .= '</form>' . "\n";
        return $html;
    }

    public static function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_frontend'])) {
            $_SESSION['csrf_frontend'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_frontend'];
    }

    public static function verifyCsrf(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $_POST['csrf_token'] ?? '';
        return !empty($_SESSION['csrf_frontend']) && hash_equals($_SESSION['csrf_frontend'], $token);
    }
}
