<?php
declare(strict_types=1);

namespace Easeo\Cms\Branding;

use Easeo\Cms\Content\ContentRepository;

final class BrandConfig
{
    /**
     * Return :root { --color-* and --font-* custom properties } CSS block.
     */
    public static function cssProperties(): string
    {
        $colors = [
            'primary'   => ContentRepository::siteValue('brand.color_primary',   '#2563EB'),
            'secondary' => ContentRepository::siteValue('brand.color_secondary', '#EA580C'),
            'dark'      => ContentRepository::siteValue('brand.color_dark',      '#111827'),
            'darker'    => ContentRepository::siteValue('brand.color_darker',    '#0B1120'),
            'surface'   => ContentRepository::siteValue('brand.color_surface',   '#1F2937'),
            'success'   => ContentRepository::siteValue('brand.color_success',   '#10B981'),
            'text'      => ContentRepository::siteValue('brand.color_text',      '#F9FAFB'),
            'muted'     => ContentRepository::siteValue('brand.color_muted',     '#9CA3AF'),
        ];
        $fonts = [
            'display' => ContentRepository::siteValue('brand.font_display', 'Outfit'),
            'body'    => ContentRepository::siteValue('brand.font_body',    'Inter'),
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

    /**
     * Build a Google Fonts CSS2 URL for the configured display + body fonts.
     */
    public static function googleFontsUrl(): string
    {
        $display = ContentRepository::siteValue('brand.font_display', 'Outfit');
        $body    = ContentRepository::siteValue('brand.font_body',    'Inter');
        $displayWeights = ContentRepository::siteValue('brand.font_display_weights', '600;700;800');
        $bodyWeights    = ContentRepository::siteValue('brand.font_body_weights',    '400;500;600');

        $families = [];
        $families[] = urlencode($display) . ':wght@' . $displayWeights;
        if ($body !== $display) {
            $families[] = urlencode($body) . ':wght@' . $bodyWeights;
        }

        return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
    }

    /**
     * Inline Tailwind config that references the CSS custom properties.
     */
    public static function tailwindConfig(): string
    {
        return "\n    tailwind.config = {\n"
             . "        theme: {\n"
             . "            extend: {\n"
             . "                colors: {\n"
             . "                    primary: 'var(--color-primary)',\n"
             . "                    secondary: 'var(--color-secondary)',\n"
             . "                    dark: 'var(--color-dark)',\n"
             . "                    darker: 'var(--color-darker)',\n"
             . "                    surface: 'var(--color-surface)',\n"
             . "                    success: 'var(--color-success)',\n"
             . "                    'brand-text': 'var(--color-text)',\n"
             . "                    muted: 'var(--color-muted)',\n"
             . "                },\n"
             . "                fontFamily: {\n"
             . "                    display: 'var(--font-display)',\n"
             . "                    body: 'var(--font-body)',\n"
             . "                }\n"
             . "            }\n"
             . "        }\n"
             . "    }";
    }
}
