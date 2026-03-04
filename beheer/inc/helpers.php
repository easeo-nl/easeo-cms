<?php
/**
 * EASEO CMS — Admin UI helpers, auto field config
 */

/**
 * Derive field type from key naming conventions.
 *
 * Naming rules:
 * - *_afbeelding, *_logo, *_foto, *_image → image picker
 * - *_tekst, *_inhoud, *_beschrijving, *_content → textarea/rich
 * - *_email → email input
 * - *_telefoon, *_tel → tel input
 * - *_url, *_link, *_website → url input
 * - *_datum, *_date → date input
 * - *_kleur, *_color → color picker
 * - *_nummer, *_aantal, *_prijs → number input
 * - *_aan, *_actief, *_toon, *_enabled → checkbox/toggle
 * - *_id → hidden/select
 * - *_embed, *_code, *_script, *_custom → textarea code
 * - meta_* → text (seo)
 * - Everything else → text input
 */
function auto_field_config(string $key, $value = ''): array {
    $key_lower = strtolower($key);
    $label = generate_label($key);

    // Image fields
    if (preg_match('/(afbeelding|logo|foto|image|favicon|thumbnail|thumb)$/', $key_lower)) {
        return ['type' => 'image', 'label' => $label, 'key' => $key];
    }

    // Rich text / textarea
    if (preg_match('/(tekst|inhoud|beschrijving|content|bio|samenvatting)$/', $key_lower)) {
        return ['type' => 'textarea', 'label' => $label, 'key' => $key];
    }

    // Email
    if (preg_match('/email$/', $key_lower)) {
        return ['type' => 'email', 'label' => $label, 'key' => $key];
    }

    // Phone
    if (preg_match('/(telefoon|tel)$/', $key_lower)) {
        return ['type' => 'tel', 'label' => $label, 'key' => $key];
    }

    // URL
    if (preg_match('/(url|link|website)$/', $key_lower)) {
        return ['type' => 'url', 'label' => $label, 'key' => $key];
    }

    // Date
    if (preg_match('/(datum|date)$/', $key_lower)) {
        return ['type' => 'date', 'label' => $label, 'key' => $key];
    }

    // Color
    if (preg_match('/(kleur|color)$/', $key_lower)) {
        return ['type' => 'color', 'label' => $label, 'key' => $key];
    }

    // Number
    if (preg_match('/(nummer|aantal|prijs|price|number|count)$/', $key_lower)) {
        return ['type' => 'number', 'label' => $label, 'key' => $key];
    }

    // Boolean toggle
    if (preg_match('/(aan|actief|toon|enabled|active|visible)$/', $key_lower)) {
        return ['type' => 'checkbox', 'label' => $label, 'key' => $key];
    }

    // Code / embed
    if (preg_match('/(embed|code|script|custom|html)$/', $key_lower)) {
        return ['type' => 'code', 'label' => $label, 'key' => $key];
    }

    // Default text
    return ['type' => 'text', 'label' => $label, 'key' => $key];
}

/**
 * Generate a human-readable label from a key.
 * Converts snake_case to Title Case, with Dutch-friendly output.
 */
function generate_label(string $key): string {
    $label = str_replace('_', ' ', $key);
    $label = ucfirst($label);
    // Capitalize common abbreviations
    $label = str_ireplace(['url', 'seo', 'html', 'css', 'gtm', 'ga4', 'kvk', 'btw'], ['URL', 'SEO', 'HTML', 'CSS', 'GTM', 'GA4', 'KVK', 'BTW'], $label);
    return $label;
}

/**
 * Render a form field based on auto_field_config.
 */
function get_field_tooltip(string $key, string $type): string {
    $tooltips = [
        'meta_title' => 'Maximaal 60 tekens. Dit is wat Google toont als paginatitel in de zoekresultaten.',
        'meta_description' => 'Maximaal 155 tekens. De korte beschrijving onder de paginatitel in Google.',
    ];
    if (isset($tooltips[$key])) {
        return ' <span class="help-tooltip" data-help="' . e($tooltips[$key]) . '">?</span>';
    }
    if ($type === 'image') {
        return ' <span class="help-tooltip" data-help="Klik om een afbeelding te kiezen uit de mediabibliotheek.">?</span>';
    }
    return '';
}

function render_field(array $config, $value = '', string $prefix = ''): string {
    $name = $prefix ? "{$prefix}[{$config['key']}]" : $config['key'];
    $id = str_replace(['[', ']'], ['-', ''], $name);
    $val = e((string)$value);
    $label = e($config['label']);
    $tooltip = get_field_tooltip($config['key'], $config['type']);

    $html = '<div class="mb-4">' . "\n";

    switch ($config['type']) {
        case 'textarea':
            $html .= '  <label for="' . $id . '" class="block text-sm font-medium text-gray-300 mb-1">' . $label . $tooltip . '</label>' . "\n";
            $html .= '  <textarea id="' . $id . '" name="' . $name . '" rows="5" class="admin-input w-full">' . $val . '</textarea>' . "\n";
            break;

        case 'code':
            $html .= '  <label for="' . $id . '" class="block text-sm font-medium text-gray-300 mb-1">' . $label . $tooltip . '</label>' . "\n";
            $html .= '  <textarea id="' . $id . '" name="' . $name . '" rows="6" class="admin-input w-full font-mono text-sm">' . $val . '</textarea>' . "\n";
            break;

        case 'image':
            $html .= '  <label class="block text-sm font-medium text-gray-300 mb-1">' . $label . $tooltip . '</label>' . "\n";
            $html .= '  <div class="flex items-center gap-3">' . "\n";
            $html .= '    <input type="text" id="' . $id . '" name="' . $name . '" value="' . $val . '" class="admin-input flex-1" placeholder="/images/uploads/...">' . "\n";
            $html .= '    <button type="button" onclick="openMediaPicker(\'' . $id . '\')" class="btn-admin-sm">Kies</button>' . "\n";
            if ($value) {
                $html .= '    <img src="' . $val . '" class="h-10 w-10 object-cover rounded" alt="">' . "\n";
            }
            $html .= '  </div>' . "\n";
            break;

        case 'checkbox':
            $checked = $value ? ' checked' : '';
            $html .= '  <label class="flex items-center gap-2 cursor-pointer">' . "\n";
            $html .= '    <input type="hidden" name="' . $name . '" value="0">' . "\n";
            $html .= '    <input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . $checked . ' class="w-4 h-4 rounded">' . "\n";
            $html .= '    <span class="text-sm text-gray-300">' . $label . $tooltip . '</span>' . "\n";
            $html .= '  </label>' . "\n";
            break;

        case 'color':
            $html .= '  <label for="' . $id . '" class="block text-sm font-medium text-gray-300 mb-1">' . $label . $tooltip . '</label>' . "\n";
            $html .= '  <div class="flex items-center gap-2">' . "\n";
            $html .= '    <input type="color" id="' . $id . '" name="' . $name . '" value="' . ($val ?: '#000000') . '" class="w-10 h-10 rounded cursor-pointer border-0">' . "\n";
            $html .= '    <input type="text" value="' . $val . '" class="admin-input w-28" oninput="document.getElementById(\'' . $id . '\').value=this.value" >' . "\n";
            $html .= '  </div>' . "\n";
            break;

        default: // text, email, tel, url, date, number
            $type = in_array($config['type'], ['email', 'tel', 'url', 'date', 'number']) ? $config['type'] : 'text';
            $html .= '  <label for="' . $id . '" class="block text-sm font-medium text-gray-300 mb-1">' . $label . $tooltip . '</label>' . "\n";
            $html .= '  <input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . $val . '" class="admin-input w-full">' . "\n";
            break;
    }

    $html .= '</div>' . "\n";
    return $html;
}

/**
 * Sanitize input value
 */
function sanitize_input($value) {
    if (is_array($value)) {
        return array_map('sanitize_input', $value);
    }
    return trim((string)$value);
}

/**
 * Flash message display
 */
function render_flash(): string {
    $html = '';
    $error = flash_error();
    $success = flash_success();

    if ($error) {
        $html .= '<div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm">' . e($error) . '</div>' . "\n";
    }
    if ($success) {
        $html .= '<div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg text-sm">' . e($success) . '</div>' . "\n";
    }

    return $html;
}
