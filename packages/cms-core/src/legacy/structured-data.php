<?php
/**
 * EASEO CMS — Structured Data (JSON-LD Schema.org)
 * Auto-generates Organization, WebSite, BreadcrumbList, Article schemas
 */

function get_base_url(): string {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/**
 * Organization / LocalBusiness — on every page
 */
function schema_organization(): array {
    global $site;
    $c = $site['company'] ?? [];
    $baseUrl = get_base_url();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $c['name'] ?? '',
        'url' => $baseUrl,
    ];

    if (!empty($c['tagline'])) $schema['description'] = $c['tagline'];
    if (!empty($c['phone'])) $schema['telephone'] = $c['phone'];
    if (!empty($c['email'])) $schema['email'] = $c['email'];

    if (!empty($c['address']) || !empty($c['city'])) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $c['address'] ?? '',
            'postalCode' => $c['postcode'] ?? '',
            'addressLocality' => $c['city'] ?? '',
            'addressCountry' => 'NL',
        ];
    }

    if (!empty($c['kvk'])) {
        $schema['leiCode'] = $c['kvk'];
    }
    if (!empty($c['btw'])) {
        $schema['taxID'] = $c['btw'];
    }

    if (!empty($site['brand']['logo'])) {
        $logo = $site['brand']['logo'];
        if (!str_starts_with($logo, 'http')) {
            $logo = $baseUrl . '/' . ltrim($logo, '/');
        }
        $schema['logo'] = $logo;
    }

    $social = $site['social'] ?? [];
    $sameAs = array_values(array_filter(array_map('trim', [
        $social['linkedin'] ?? '',
        $social['instagram'] ?? '',
        $social['facebook'] ?? '',
        $social['twitter'] ?? '',
        $social['youtube'] ?? '',
    ])));
    if (!empty($sameAs)) {
        $schema['sameAs'] = $sameAs;
    }

    return $schema;
}

/**
 * WebSite — homepage only
 */
function schema_website(): array {
    global $site;
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site['company']['name'] ?? '',
        'url' => get_base_url(),
    ];
}

/**
 * BreadcrumbList — all pages except homepage
 */
function schema_breadcrumbs(string $title, string $path, array $parents = []): array {
    $baseUrl = get_base_url();
    $items = [];
    $pos = 1;

    $items[] = [
        '@type' => 'ListItem',
        'position' => $pos++,
        'name' => 'Home',
        'item' => $baseUrl . '/',
    ];

    foreach ($parents as $parent) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $parent['name'],
            'item' => $baseUrl . '/' . ltrim($parent['slug'], '/'),
        ];
    }

    $items[] = [
        '@type' => 'ListItem',
        'position' => $pos,
        'name' => $title,
        'item' => $baseUrl . '/' . ltrim($path, '/'),
    ];

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

/**
 * Article / BlogPosting — blog posts
 */
function schema_article(array $post): array {
    global $site;
    $baseUrl = get_base_url();
    $companyName = $site['company']['name'] ?? '';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['titel'] ?? $post['title'] ?? '',
        'description' => $post['samenvatting'] ?? $post['meta_description'] ?? '',
        'datePublished' => date('c', strtotime($post['datum'] ?? $post['created_at'] ?? 'now')),
        'dateModified' => date('c', strtotime($post['bijgewerkt'] ?? $post['updated_at'] ?? $post['datum'] ?? 'now')),
        'author' => [
            '@type' => !empty($post['auteur']) ? 'Person' : 'Organization',
            'name' => $post['auteur'] ?: $companyName,
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $companyName,
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $baseUrl . '/blog/' . ($post['slug'] ?? ''),
        ],
    ];

    if (!empty($post['afbeelding'])) {
        $img = $post['afbeelding'];
        if (!str_starts_with($img, 'http')) {
            $img = $baseUrl . '/' . ltrim($img, '/');
        }
        $schema['image'] = $img;
    }

    if (!empty($site['brand']['logo'])) {
        $logo = $site['brand']['logo'];
        if (!str_starts_with($logo, 'http')) {
            $logo = $baseUrl . '/' . ltrim($logo, '/');
        }
        $schema['publisher']['logo'] = [
            '@type' => 'ImageObject',
            'url' => $logo,
        ];
    }

    $schema['speakable'] = [
        '@type' => 'SpeakableSpecification',
        'cssSelector' => ['h1', '.content-area p:first-of-type'],
    ];

    return $schema;
}

/**
 * Render all schemas as JSON-LD script tags
 */
function render_structured_data(array $schemas): void {
    foreach ($schemas as $schema) {
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . "\n";
            echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo "\n</script>\n";
        }
    }
}
