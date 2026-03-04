<?php
/**
 * EASEO CMS — Brand: CSS custom properties and Google Fonts
 */

function brand_css_properties(): string {
    $colors = [
        'primary'   => site('brand.color_primary', '#2563EB'),
        'secondary' => site('brand.color_secondary', '#EA580C'),
        'dark'      => site('brand.color_dark', '#111827'),
        'darker'    => site('brand.color_darker', '#0B1120'),
        'surface'   => site('brand.color_surface', '#1F2937'),
        'success'   => site('brand.color_success', '#10B981'),
        'text'      => site('brand.color_text', '#F9FAFB'),
        'muted'     => site('brand.color_muted', '#9CA3AF'),
    ];

    $fonts = [
        'display' => site('brand.font_display', 'Outfit'),
        'body'    => site('brand.font_body', 'Inter'),
    ];

    $css = ":root {\n";
    foreach ($colors as $name => $value) {
        $css .= "    --color-{$name}: {$value};\n";
    }
    $css .= "    --font-display: '{$fonts['display']}', sans-serif;\n";
    $css .= "    --font-body: '{$fonts['body']}', sans-serif;\n";
    $css .= "}\n";

    return $css;
}

function brand_google_fonts_url(): string {
    $display = site('brand.font_display', 'Outfit');
    $body = site('brand.font_body', 'Inter');
    $displayWeights = site('brand.font_display_weights', '600;700;800');
    $bodyWeights = site('brand.font_body_weights', '400;500;600');

    $families = [];
    $families[] = urlencode($display) . ':wght@' . str_replace(';', ';', $displayWeights);
    if ($body !== $display) {
        $families[] = urlencode($body) . ':wght@' . str_replace(';', ';', $bodyWeights);
    }

    return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
}

function brand_tailwind_config(): string {
    return "
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: 'var(--color-primary)',
                    secondary: 'var(--color-secondary)',
                    dark: 'var(--color-dark)',
                    darker: 'var(--color-darker)',
                    surface: 'var(--color-surface)',
                    success: 'var(--color-success)',
                    'brand-text': 'var(--color-text)',
                    muted: 'var(--color-muted)',
                },
                fontFamily: {
                    display: 'var(--font-display)',
                    body: 'var(--font-body)',
                }
            }
        }
    }";
}
