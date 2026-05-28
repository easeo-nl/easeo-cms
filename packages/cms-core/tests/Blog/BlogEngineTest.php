<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Blog;

use Easeo\Cms\Blog\BlogEngine;
use Easeo\Cms\Content\ContentRepository;
use PHPUnit\Framework\TestCase;

final class BlogEngineTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/blog-engine-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
        ContentRepository::resetCache();
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        ContentRepository::resetCache();
        if (is_dir($this->tmpDataDir)) {
            $this->rmrf($this->tmpDataDir);
        }
    }

    private function rmrf(string $dir): void
    {
        foreach (glob("$dir/*") ?: [] as $f) {
            is_dir($f) ? $this->rmrf($f) : unlink($f);
        }
        rmdir($dir);
    }

    // -------------------------------------------------------------------------
    // getPosts / getPostsData
    // -------------------------------------------------------------------------

    public function test_get_posts_returns_empty_when_no_file(): void
    {
        $this->assertSame([], BlogEngine::getPosts());
    }

    public function test_get_posts_data_returns_empty_when_no_file(): void
    {
        $this->assertSame([], BlogEngine::getPostsData());
    }

    // -------------------------------------------------------------------------
    // createPost + getPostById round-trip
    // Fields are Dutch: titel, samenvatting, inhoud, afbeelding, categorie, tags,
    // auteur, status ('gepubliceerd'|'concept'), datum, bijgewerkt, meta_title,
    // meta_description, id, slug
    // -------------------------------------------------------------------------

    public function test_create_then_get_by_id_round_trip(): void
    {
        $created = BlogEngine::createPost([
            'titel'  => 'Mijn eerste post',
            'inhoud' => 'Hallo wereld',
            'status' => 'gepubliceerd',
        ]);
        $this->assertArrayHasKey('id', $created);
        $this->assertNotEmpty($created['id']);

        $fetched = BlogEngine::getPostById($created['id']);
        $this->assertNotNull($fetched);
        $this->assertSame('Mijn eerste post', $fetched['titel']);
    }

    public function test_create_generates_slug_from_titel(): void
    {
        $created = BlogEngine::createPost([
            'titel'  => 'Hello World Test',
            'inhoud' => 'body',
            'status' => 'gepubliceerd',
        ]);
        $this->assertArrayHasKey('slug', $created);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $created['slug']);
        $this->assertStringContainsString('hello', $created['slug']);
    }

    public function test_create_defaults_status_to_concept(): void
    {
        $created = BlogEngine::createPost(['titel' => 'Zonder status', 'inhoud' => '']);
        $this->assertSame('concept', $created['status']);
    }

    public function test_create_rejects_invalid_status(): void
    {
        $created = BlogEngine::createPost(['titel' => 'Ongeldige status', 'inhoud' => '', 'status' => 'published']);
        $this->assertSame('concept', $created['status']);
    }

    // -------------------------------------------------------------------------
    // getPostBySlug
    // -------------------------------------------------------------------------

    public function test_get_post_by_slug_returns_null_for_unknown(): void
    {
        BlogEngine::createPost(['titel' => 'Foo', 'inhoud' => 'bar', 'status' => 'gepubliceerd']);
        $this->assertNull(BlogEngine::getPostBySlug('bestaat-niet'));
    }

    public function test_get_post_by_slug_returns_array_for_known(): void
    {
        $created = BlogEngine::createPost(['titel' => 'Test Post', 'inhoud' => 'body', 'status' => 'gepubliceerd']);
        $found = BlogEngine::getPostBySlug($created['slug']);
        $this->assertNotNull($found);
        $this->assertSame($created['id'], $found['id']);
    }

    // -------------------------------------------------------------------------
    // getPublishedPosts — uses status 'gepubliceerd' (Dutch) not 'published'
    // -------------------------------------------------------------------------

    public function test_get_published_posts_excludes_concepts(): void
    {
        BlogEngine::createPost(['titel' => 'Gepubliceerd', 'inhoud' => '', 'status' => 'gepubliceerd']);
        BlogEngine::createPost(['titel' => 'Concept',      'inhoud' => '', 'status' => 'concept']);
        $published = BlogEngine::getPublishedPosts();
        $this->assertCount(1, $published);
        $this->assertSame('Gepubliceerd', $published[0]['titel']);
    }

    public function test_get_published_posts_excludes_future_datum(): void
    {
        BlogEngine::createPost([
            'titel'  => 'Toekomst',
            'inhoud' => '',
            'status' => 'gepubliceerd',
            'datum'  => '2099-01-01 00:00:00',
        ]);
        $this->assertCount(0, BlogEngine::getPublishedPosts());
    }

    // -------------------------------------------------------------------------
    // updatePost
    // -------------------------------------------------------------------------

    public function test_update_post_modifies_existing(): void
    {
        $created = BlogEngine::createPost(['titel' => 'Origineel', 'inhoud' => '', 'status' => 'concept']);
        $this->assertTrue(BlogEngine::updatePost($created['id'], ['titel' => 'Bijgewerkt']));
        ContentRepository::resetCache();
        $fetched = BlogEngine::getPostById($created['id']);
        $this->assertSame('Bijgewerkt', $fetched['titel']);
    }

    public function test_update_returns_false_for_unknown_id(): void
    {
        $this->assertFalse(BlogEngine::updatePost('bestaat-niet', ['titel' => 'x']));
    }

    // -------------------------------------------------------------------------
    // deletePost
    // -------------------------------------------------------------------------

    public function test_delete_post_removes_existing(): void
    {
        $created = BlogEngine::createPost(['titel' => 'Verwijder mij', 'inhoud' => '', 'status' => 'concept']);
        $this->assertTrue(BlogEngine::deletePost($created['id']));
        $this->assertNull(BlogEngine::getPostById($created['id']));
    }

    public function test_delete_returns_false_for_unknown_id(): void
    {
        $this->assertFalse(BlogEngine::deletePost('bestaat-niet'));
    }

    // -------------------------------------------------------------------------
    // getCategories — reads top-level 'categories' key from posts.json
    // (NOT derived from individual posts)
    // -------------------------------------------------------------------------

    public function test_get_categories_returns_empty_when_no_file(): void
    {
        $this->assertSame([], BlogEngine::getCategories());
    }

    public function test_get_categories_returns_top_level_categories(): void
    {
        $data = ['posts' => [], 'categories' => ['tech', 'design', 'web']];
        ContentRepository::saveJson('posts.json', $data);
        ContentRepository::resetCache();
        $cats = BlogEngine::getCategories();
        $this->assertSame(['tech', 'design', 'web'], $cats);
    }

    // -------------------------------------------------------------------------
    // generateSlug
    // -------------------------------------------------------------------------

    public function test_generate_slug_lowercases_and_kebabs(): void
    {
        $slug = BlogEngine::generateSlug('Hello World Test');
        $this->assertSame('hello-world-test', $slug);
    }

    public function test_generate_slug_strips_special_chars(): void
    {
        $slug = BlogEngine::generateSlug('Test 123 & More!');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        $this->assertStringContainsString('test', $slug);
        $this->assertStringContainsString('123', $slug);
    }

    public function test_generate_slug_no_leading_or_trailing_hyphens(): void
    {
        $slug = BlogEngine::generateSlug('!!! Test !!!');
        $this->assertStringStartsNotWith('-', $slug);
        $this->assertStringEndsNotWith('-', $slug);
    }

    // -------------------------------------------------------------------------
    // paginatePosts — returns {posts, page, total_pages, total}
    // -------------------------------------------------------------------------

    public function test_paginate_returns_correct_shape(): void
    {
        $posts = [];
        for ($i = 1; $i <= 25; $i++) {
            $posts[] = ['id' => "p$i", 'titel' => "Post $i", 'datum' => '2026-01-01'];
        }
        $result = BlogEngine::paginatePosts($posts, 1, 10);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('posts', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function test_paginate_returns_correct_slice(): void
    {
        $posts = [];
        for ($i = 1; $i <= 25; $i++) {
            $posts[] = ['id' => "p$i", 'titel' => "Post $i", 'datum' => '2026-01-01'];
        }
        $result = BlogEngine::paginatePosts($posts, 1, 10);
        $this->assertCount(10, $result['posts']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(3, $result['total_pages']);
        $this->assertSame(25, $result['total']);
    }

    public function test_paginate_last_page_has_remaining_items(): void
    {
        $posts = [];
        for ($i = 1; $i <= 25; $i++) {
            $posts[] = ['id' => "p$i", 'titel' => "Post $i", 'datum' => '2026-01-01'];
        }
        $result = BlogEngine::paginatePosts($posts, 3, 10);
        $this->assertCount(5, $result['posts']);
    }

    public function test_paginate_empty_array_returns_one_page(): void
    {
        $result = BlogEngine::paginatePosts([], 1, 10);
        $this->assertSame(1, $result['total_pages']);
        $this->assertSame(0, $result['total']);
        $this->assertSame([], $result['posts']);
    }

    // -------------------------------------------------------------------------
    // renderPostCard
    // -------------------------------------------------------------------------

    public function test_render_post_card_includes_titel_and_slug(): void
    {
        $post = [
            'id'          => 'p1',
            'slug'        => 'mijn-post',
            'titel'       => 'Mijn Blog Post',
            'samenvatting' => 'Een korte samenvatting',
            'datum'       => '2026-01-01 00:00:00',
            'status'      => 'gepubliceerd',
            'afbeelding'  => '',
            'categorie'   => '',
        ];
        $html = BlogEngine::renderPostCard($post);
        $this->assertStringContainsString('Mijn Blog Post', $html);
        $this->assertStringContainsString('mijn-post', $html);
    }

    public function test_render_post_card_returns_article_tag(): void
    {
        $post = [
            'id'    => 'p1',
            'slug'  => 'test',
            'titel' => 'Test',
            'datum' => '2026-01-01 00:00:00',
        ];
        $html = BlogEngine::renderPostCard($post);
        $this->assertStringContainsString('<article', $html);
        $this->assertStringContainsString('</article>', $html);
    }

    public function test_render_post_card_includes_blog_url(): void
    {
        $post = [
            'id'    => 'p1',
            'slug'  => 'mijn-slug',
            'titel' => 'Test',
            'datum' => '2026-01-01 00:00:00',
        ];
        $html = BlogEngine::renderPostCard($post);
        $this->assertStringContainsString('/blog/mijn-slug', $html);
    }
}
