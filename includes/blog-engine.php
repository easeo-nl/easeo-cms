<?php
/**
 * EASEO CMS — Blog engine: CRUD, pagination, rendering
 * Uses posts.json with structure: {settings:{}, posts:[], categories:[]}
 */
require_once __DIR__ . '/content.php';

function get_posts_data(): array {
    return load_json('posts.json');
}

function get_posts(): array {
    $data = get_posts_data();
    return $data['posts'] ?? [];
}

function save_posts(array $posts): bool {
    $data = get_posts_data();
    $data['posts'] = $posts;
    return save_json('posts.json', $data);
}

function get_published_posts(): array {
    $posts = get_posts();
    $now = date('Y-m-d H:i:s');
    return array_filter($posts, fn($p) =>
        ($p['status'] ?? 'concept') === 'gepubliceerd' &&
        ($p['datum'] ?? '') <= $now
    );
}

function get_post_by_slug(string $slug): ?array {
    foreach (get_posts() as $post) {
        if (($post['slug'] ?? '') === $slug) return $post;
    }
    return null;
}

function get_post_by_id(string $id): ?array {
    foreach (get_posts() as $post) {
        if (($post['id'] ?? '') === $id) return $post;
    }
    return null;
}

function create_post(array $data): array {
    $posts = get_posts();

    $slug = generate_slug($data['titel'] ?? 'post');
    $baseSlug = $slug;
    $counter = 1;
    while (get_post_by_slug($slug)) {
        $slug = $baseSlug . '-' . $counter++;
    }

    $post = [
        'id' => substr(md5(uniqid(mt_rand(), true)), 0, 12),
        'titel' => trim($data['titel'] ?? ''),
        'slug' => $slug,
        'samenvatting' => trim($data['samenvatting'] ?? ''),
        'inhoud' => $data['inhoud'] ?? '',
        'afbeelding' => $data['afbeelding'] ?? '',
        'categorie' => trim($data['categorie'] ?? ''),
        'tags' => $data['tags'] ?? '',
        'auteur' => $data['auteur'] ?? ($_SESSION['easeo_admin']['naam'] ?? ''),
        'status' => in_array($data['status'] ?? '', ['gepubliceerd', 'concept']) ? $data['status'] : 'concept',
        'datum' => $data['datum'] ?? date('Y-m-d H:i:s'),
        'bijgewerkt' => date('Y-m-d H:i:s'),
        'meta_title' => trim($data['meta_title'] ?? ''),
        'meta_description' => trim($data['meta_description'] ?? ''),
    ];

    $posts[] = $post;
    save_posts($posts);
    return $post;
}

function update_post(string $id, array $data): bool {
    $posts = get_posts();
    foreach ($posts as &$post) {
        if ($post['id'] === $id) {
            if (isset($data['titel']) && $data['titel'] !== $post['titel']) {
                $newSlug = generate_slug($data['titel']);
                $existing = get_post_by_slug($newSlug);
                if (!$existing || $existing['id'] === $id) {
                    $post['slug'] = $newSlug;
                }
            }

            foreach (['titel', 'samenvatting', 'inhoud', 'afbeelding', 'categorie', 'tags', 'auteur', 'status', 'datum', 'meta_title', 'meta_description'] as $field) {
                if (isset($data[$field])) {
                    $post[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                }
            }
            $post['bijgewerkt'] = date('Y-m-d H:i:s');

            save_posts($posts);
            return true;
        }
    }
    return false;
}

function delete_post(string $id): bool {
    $posts = get_posts();
    foreach ($posts as $idx => $post) {
        if ($post['id'] === $id) {
            array_splice($posts, $idx, 1);
            save_posts($posts);
            return true;
        }
    }
    return false;
}

function get_categories(): array {
    $data = get_posts_data();
    return $data['categories'] ?? [];
}

function paginate_posts(array $posts, int $page = 1, int $perPage = 9): array {
    usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));

    $total = count($posts);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'posts' => array_slice($posts, $offset, $perPage),
        'page' => $page,
        'total_pages' => $totalPages,
        'total' => $total,
    ];
}

function generate_slug(string $text): string {
    $slug = strtolower($text);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

function render_post_card(array $post): string {
    $url = '/blog/' . e($post['slug']);
    $img = $post['afbeelding'] ?? '';
    $title = e($post['titel'] ?? '');
    $summary = e($post['samenvatting'] ?? '');
    $date = date('d M Y', strtotime($post['datum'] ?? 'now'));
    $cat = e($post['categorie'] ?? '');

    $html = '<article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">' . "\n";

    if ($img) {
        $html .= '  <a href="' . $url . '"><img src="' . e($img) . '" alt="' . $title . '" class="w-full h-48 object-cover"></a>' . "\n";
    }

    $html .= '  <div class="p-5">' . "\n";
    if ($cat) {
        $html .= '    <span class="text-xs font-medium text-primary uppercase tracking-wider">' . $cat . '</span>' . "\n";
    }
    $html .= '    <h2 class="text-lg font-display font-bold mt-1 mb-2"><a href="' . $url . '" class="text-dark hover:text-primary transition-colors">' . $title . '</a></h2>' . "\n";
    if ($summary) {
        $html .= '    <p class="text-muted text-sm mb-3">' . $summary . '</p>' . "\n";
    }
    $html .= '    <div class="flex items-center justify-between">' . "\n";
    $html .= '      <time class="text-xs text-gray-400">' . $date . '</time>' . "\n";
    $html .= '      <a href="' . $url . '" class="text-sm text-primary font-medium hover:underline">Lees meer &rarr;</a>' . "\n";
    $html .= '    </div>' . "\n";
    $html .= '  </div>' . "\n";
    $html .= '</article>' . "\n";

    return $html;
}
