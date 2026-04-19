<?php
/**
 * EASEO CMS — Navigation renderer
 * Uses navigation.json with keys: main, footer
 */

function get_dynamic_page_menu_items(): array {
    $pagesData = load_json('pages.json');
    $pages = $pagesData['pages'] ?? [];
    $items = [];
    $children = [];

    // Sort by sort_order
    usort($pages, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

    foreach ($pages as $p) {
        if (empty($p['show_in_menu']) || $p['status'] !== 'published') continue;

        $menuItem = [
            'url' => '/' . $p['slug'],
            'label' => $p['menu_label'] ?: $p['title'],
        ];

        if (!empty($p['parent'])) {
            $children[$p['parent']][] = $menuItem;
        } else {
            $menuItem['_page_id'] = $p['id'];
            $menuItem['children'] = [];
            $items[] = $menuItem;
        }
    }

    // Attach children to parents
    foreach ($items as &$item) {
        if (isset($children[$item['_page_id']])) {
            $item['children'] = $children[$item['_page_id']];
        }
        unset($item['_page_id']);
    }
    unset($item);

    return $items;
}

function merge_nav_with_dynamic(array $manualItems): array {
    $dynamicItems = get_dynamic_page_menu_items();
    if (empty($dynamicItems)) return $manualItems;

    // Collect URLs already in manual menu
    $existingUrls = [];
    foreach ($manualItems as $item) {
        $existingUrls[] = rtrim($item['url'] ?? '', '/');
        foreach (($item['children'] ?? []) as $child) {
            $existingUrls[] = rtrim($child['url'] ?? '', '/');
        }
    }

    // Add dynamic items not already in manual menu
    foreach ($dynamicItems as $dynItem) {
        $dynUrl = rtrim($dynItem['url'], '/');
        if (!in_array($dynUrl, $existingUrls)) {
            $manualItems[] = $dynItem;
        }
    }

    return $manualItems;
}

function render_main_nav(): string {
    global $navigation;
    $items = merge_nav_with_dynamic($navigation['main'] ?? []);
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $current = rtrim($current, '/') ?: '/';

    $html = '<nav class="hidden md:flex items-center space-x-1" id="main-nav">' . "\n";

    foreach ($items as $item) {
        $url = e($item['url'] ?? '#');
        $label = e($item['label'] ?? '');
        $itemPath = rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/') ?: '/';
        $active = ($current === $itemPath) ? ' text-primary font-semibold' : ' text-gray-700 hover:text-primary';
        $hasChildren = !empty($item['children']);

        if ($hasChildren) {
            $html .= '<div class="relative group">' . "\n";
            $html .= '  <button class="px-3 py-2 rounded-md text-sm font-medium transition-colors' . $active . '">' . $label . ' <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>' . "\n";
            $html .= '  <div class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">' . "\n";

            foreach ($item['children'] as $child) {
                $childUrl = e($child['url'] ?? '#');
                $childLabel = e($child['label'] ?? '');
                $html .= '    <a href="' . $childUrl . '" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary">' . $childLabel . '</a>' . "\n";
            }

            $html .= '  </div>' . "\n";
            $html .= '</div>' . "\n";
        } else {
            $html .= '<a href="' . $url . '" class="px-3 py-2 rounded-md text-sm font-medium transition-colors' . $active . '">' . $label . '</a>' . "\n";
        }
    }

    $html .= '</nav>' . "\n";
    return $html;
}

function render_mobile_nav(): string {
    global $navigation;
    $items = merge_nav_with_dynamic($navigation['main'] ?? []);

    $html = '<div id="mobile-menu" class="hidden md:hidden bg-white border-t">' . "\n";
    $html .= '  <div class="px-4 py-3 space-y-1">' . "\n";

    foreach ($items as $item) {
        $url = e($item['url'] ?? '#');
        $label = e($item['label'] ?? '');
        $html .= '    <a href="' . $url . '" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-primary">' . $label . '</a>' . "\n";

        if (!empty($item['children'])) {
            foreach ($item['children'] as $child) {
                $childUrl = e($child['url'] ?? '#');
                $childLabel = e($child['label'] ?? '');
                $html .= '    <a href="' . $childUrl . '" class="block pl-6 py-2 text-sm text-gray-600 hover:text-primary">' . $childLabel . '</a>' . "\n";
            }
        }
    }

    $html .= '  </div>' . "\n";
    $html .= '</div>' . "\n";
    return $html;
}

function render_footer_nav(): string {
    global $navigation;
    $items = $navigation['footer'] ?? [];

    $html = '';
    foreach ($items as $item) {
        $url = e($item['url'] ?? '#');
        $label = e($item['label'] ?? '');
        $html .= '<a href="' . $url . '" class="text-gray-400 hover:text-white text-sm transition-colors">' . $label . '</a>' . "\n";
    }
    return $html;
}
