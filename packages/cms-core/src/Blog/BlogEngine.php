<?php
declare(strict_types=1);

namespace Easeo\Cms\Blog;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Lang\Translator;

final class BlogEngine
{
    public static function getPostsData(): array
    {
        return ContentRepository::loadJson('posts.json');
    }

    public static function getPosts(): array
    {
        $data = self::getPostsData();
        return $data['posts'] ?? [];
    }

    public static function savePosts(array $posts): bool
    {
        $data = self::getPostsData();
        $data['posts'] = $posts;
        $result = ContentRepository::saveJson('posts.json', $data);
        ContentRepository::invalidateJsonCache('posts.json');
        return $result;
    }

    public static function getPublishedPosts(): array
    {
        $posts = self::getPosts();
        $now = date('Y-m-d H:i:s');
        return array_values(array_filter(
            $posts,
            fn($p) => ($p['status'] ?? 'concept') === 'gepubliceerd'
                && ($p['datum'] ?? '') <= $now
        ));
    }

    public static function getPostBySlug(string $slug): ?array
    {
        foreach (self::getPosts() as $post) {
            if (($post['slug'] ?? '') === $slug) {
                return $post;
            }
        }
        return null;
    }

    public static function getPostById(string $id): ?array
    {
        foreach (self::getPosts() as $post) {
            if (($post['id'] ?? '') === $id) {
                return $post;
            }
        }
        return null;
    }

    public static function createPost(array $data): array
    {
        $posts = self::getPosts();
        $slug = self::generateSlug($data['titel'] ?? 'post');
        $baseSlug = $slug;
        $counter = 1;
        while (self::getPostBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $post = [
            'id'               => substr(md5(uniqid((string) mt_rand(), true)), 0, 12),
            'titel'            => trim($data['titel'] ?? ''),
            'slug'             => $slug,
            'samenvatting'     => trim($data['samenvatting'] ?? ''),
            'inhoud'           => $data['inhoud'] ?? '',
            'afbeelding'       => $data['afbeelding'] ?? '',
            'categorie'        => trim($data['categorie'] ?? ''),
            'tags'             => $data['tags'] ?? '',
            'auteur'           => $data['auteur'] ?? ($_SESSION['easeo_admin']['naam'] ?? ''),
            'status'           => in_array($data['status'] ?? '', ['gepubliceerd', 'concept'], true)
                                    ? $data['status']
                                    : 'concept',
            'datum'            => $data['datum'] ?? date('Y-m-d H:i:s'),
            'bijgewerkt'       => date('Y-m-d H:i:s'),
            'meta_title'       => trim($data['meta_title'] ?? ''),
            'meta_description' => trim($data['meta_description'] ?? ''),
        ];
        $posts[] = $post;
        self::savePosts($posts);
        return $post;
    }

    public static function updatePost(string $id, array $data): bool
    {
        $posts = self::getPosts();
        foreach ($posts as &$post) {
            if ($post['id'] === $id) {
                if (isset($data['titel']) && $data['titel'] !== $post['titel']) {
                    $newSlug = self::generateSlug($data['titel']);
                    $existing = self::getPostBySlug($newSlug);
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
                self::savePosts($posts);
                return true;
            }
        }
        return false;
    }

    public static function deletePost(string $id): bool
    {
        $posts = self::getPosts();
        foreach ($posts as $idx => $post) {
            if ($post['id'] === $id) {
                array_splice($posts, $idx, 1);
                self::savePosts($posts);
                return true;
            }
        }
        return false;
    }

    public static function getCategories(): array
    {
        $data = self::getPostsData();
        return $data['categories'] ?? [];
    }

    public static function paginatePosts(array $posts, int $page = 1, int $perPage = 9): array
    {
        usort($posts, fn($a, $b) => strcmp($b['datum'] ?? '', $a['datum'] ?? ''));
        $total = count($posts);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        return [
            'posts'       => array_slice($posts, $offset, $perPage),
            'page'        => $page,
            'total_pages' => $totalPages,
            'total'       => $total,
        ];
    }

    public static function generateSlug(string $text): string
    {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    public static function renderPostCard(array $post): string
    {
        $url     = '/blog/' . ContentRepository::escape($post['slug'] ?? '');
        $img     = $post['afbeelding'] ?? '';
        $title   = ContentRepository::escape($post['titel'] ?? '');
        $summary = ContentRepository::escape($post['samenvatting'] ?? '');
        $date    = date('d M Y', strtotime($post['datum'] ?? 'now'));
        $cat     = ContentRepository::escape($post['categorie'] ?? '');

        $html = '<article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">' . "\n";
        if ($img) {
            $html .= '  <a href="' . $url . '"><img src="' . ContentRepository::escape($img) . '" alt="' . $title . '" class="w-full h-48 object-cover"></a>' . "\n";
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
        $html .= '      <a href="' . $url . '" class="text-sm text-primary font-medium hover:underline">' . Translator::translate('blog_card_read_more') . '</a>' . "\n";
        $html .= '    </div>' . "\n";
        $html .= '  </div>' . "\n";
        $html .= '</article>' . "\n";
        return $html;
    }
}
