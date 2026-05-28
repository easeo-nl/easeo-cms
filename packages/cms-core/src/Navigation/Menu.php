<?php
declare(strict_types=1);

namespace Easeo\Cms\Navigation;

use Easeo\Cms\Content\ContentRepository;

/**
 * Navigation Menu engine
 * Renders main nav, mobile nav, and footer nav with dynamic page items
 */
final class Menu
{
    /**
     * Get menu items from published pages marked to show in menu
     * @return list<array<string,mixed>>
     */
    public static function getDynamicPageMenuItems(): array
    {
        $pagesData = ContentRepository::loadJson('pages.json');
        $pages = $pagesData['pages'] ?? [];
        $items = [];
        $children = [];
        // Sort by sort_order
        usort($pages, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));
        foreach ($pages as $p) {
            if (empty($p['show_in_menu']) || $p['status'] !== 'published') {
                continue;
            }
            $menuItem = ['url' => '/' . $p['slug'], 'label' => $p['menu_label'] ?: $p['title']];
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

    /**
     * Merge manual navigation items with dynamic page items
     * @param list<array<string,mixed>> $manualItems
     * @return list<array<string,mixed>>
     */
    public static function mergeNavWithDynamic(array $manualItems): array
    {
        $dynamicItems = self::getDynamicPageMenuItems();
        if (empty($dynamicItems)) {
            return $manualItems;
        }
        // Collect URLs already in manual menu
        $existingUrls = [];
        foreach ($manualItems as $item) {
            $existingUrls[] = rtrim($item['url'] ?? '', '/');
            foreach ($item['children'] ?? [] as $child) {
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

    /**
     * Render main desktop navigation with dropdowns for children
     */
    public static function renderMainNav(): string
    {
        global $navigation;
        $items = self::mergeNavWithDynamic($navigation['main'] ?? []);
        $current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $current = rtrim($current, '/') ?: '/';
        $html = '<nav class="hidden md:flex items-center space-x-1" id="main-nav">' . "\n";
        foreach ($items as $item) {
            $url = ContentRepository::escape($item['url'] ?? '#');
            $label = ContentRepository::escape($item['label'] ?? '');
            $itemPath = rtrim(parse_url($url, PHP_URL_PATH) ?? '', '/') ?: '/';
            $active = $current === $itemPath ? ' text-primary font-semibold' : ' text-gray-700 hover:text-primary';
            $hasChildren = !empty($item['children']);
            if ($hasChildren) {
                $html .= '<div class="relative group">' . "\n";
                $html .= '  <button class="px-3 py-2 rounded-md text-sm font-medium transition-colors' . $active . '">' . $label . ' <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>' . "\n";
                $html .= '  <div class="absolute left-0 mt-1 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">' . "\n";
                foreach ($item['children'] as $child) {
                    $childUrl = ContentRepository::escape($child['url'] ?? '#');
                    $childLabel = ContentRepository::escape($child['label'] ?? '');
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

    /**
     * Render mobile navigation (hidden on md and above)
     */
    public static function renderMobileNav(): string
    {
        global $navigation;
        $items = self::mergeNavWithDynamic($navigation['main'] ?? []);
        $html = '<div id="mobile-menu" class="hidden md:hidden bg-white border-t">' . "\n";
        $html .= '  <div class="px-4 py-3 space-y-1">' . "\n";
        foreach ($items as $item) {
            $url = ContentRepository::escape($item['url'] ?? '#');
            $label = ContentRepository::escape($item['label'] ?? '');
            $html .= '    <a href="' . $url . '" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-primary">' . $label . '</a>' . "\n";
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $childUrl = ContentRepository::escape($child['url'] ?? '#');
                    $childLabel = ContentRepository::escape($child['label'] ?? '');
                    $html .= '    <a href="' . $childUrl . '" class="block pl-6 py-2 text-sm text-gray-600 hover:text-primary">' . $childLabel . '</a>' . "\n";
                }
            }
        }
        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    /**
     * Render footer navigation (simple links, no children)
     */
    public static function renderFooterNav(): string
    {
        global $navigation;
        $items = $navigation['footer'] ?? [];
        $html = '';
        foreach ($items as $item) {
            $url = ContentRepository::escape($item['url'] ?? '#');
            $label = ContentRepository::escape($item['label'] ?? '');
            $html .= '<a href="' . $url . '" class="text-gray-400 hover:text-white text-sm transition-colors">' . $label . '</a>' . "\n";
        }
        return $html;
    }
}
