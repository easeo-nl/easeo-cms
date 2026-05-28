<?php
declare(strict_types=1);

namespace Easeo\Cms\Tests\Seo;

use Easeo\Cms\Content\ContentRepository;
use Easeo\Cms\Seo\StructuredData;
use PHPUnit\Framework\TestCase;

final class StructuredDataTest extends TestCase
{
    private string $tmpDataDir;

    protected function setUp(): void
    {
        $this->tmpDataDir = sys_get_temp_dir() . '/seo-test-' . uniqid('', true);
        mkdir($this->tmpDataDir, 0755, true);
        putenv("EASEO_DATA={$this->tmpDataDir}");
        ContentRepository::resetCache();

        // Set up minimal site.json with company and brand data
        file_put_contents("{$this->tmpDataDir}/site.json", json_encode([
            'company' => [
                'name' => 'Test Company',
                'email' => 'info@test.example',
                'phone' => '+31000000000',
                'address' => 'Teststreet 1',
                'postcode' => '1234AB',
                'city' => 'Testville',
                'tagline' => 'Your trusted test partner',
            ],
            'brand' => [
                'logo' => '/assets/logo.png',
            ],
            'social' => [
                'linkedin' => 'https://linkedin.com/company/test',
                'instagram' => 'https://instagram.com/test',
                'facebook' => '',
                'twitter' => '',
                'youtube' => '',
            ],
        ]));

        $_SERVER['HTTP_HOST'] = 'test.example';
        $_SERVER['HTTPS'] = 'on';
    }

    protected function tearDown(): void
    {
        putenv('EASEO_DATA');
        ContentRepository::resetCache();
        unset($_SERVER['HTTP_HOST'], $_SERVER['HTTPS']);
        if (is_dir($this->tmpDataDir)) {
            foreach (glob($this->tmpDataDir . '/*') ?: [] as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
            rmdir($this->tmpDataDir);
        }
    }

    public function testGetBaseUrlReturnsHttpsWhenHttpsOn(): void
    {
        $url = StructuredData::getBaseUrl();
        $this->assertStringStartsWith('https://test.example', $url);
    }

    public function testGetBaseUrlReturnsHttpWhenHttpsOff(): void
    {
        unset($_SERVER['HTTPS']);
        $url = StructuredData::getBaseUrl();
        $this->assertStringStartsWith('http://test.example', $url);
    }

    public function testSchemaOrganizationHasCorrectTypeAndContext(): void
    {
        $schema = StructuredData::schemaOrganization();
        $this->assertIsArray($schema);
        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('LocalBusiness', $schema['@type']);
    }

    public function testSchemaOrganizationIncludesCompanyFields(): void
    {
        $schema = StructuredData::schemaOrganization();
        $this->assertSame('Test Company', $schema['name']);
        $this->assertSame('Your trusted test partner', $schema['description']);
        $this->assertSame('+31000000000', $schema['telephone']);
        $this->assertSame('info@test.example', $schema['email']);
    }

    public function testSchemaOrganizationIncludesAddress(): void
    {
        $schema = StructuredData::schemaOrganization();
        $this->assertIsArray($schema['address']);
        $this->assertSame('PostalAddress', $schema['address']['@type']);
        $this->assertSame('Teststreet 1', $schema['address']['streetAddress']);
        $this->assertSame('1234AB', $schema['address']['postalCode']);
        $this->assertSame('Testville', $schema['address']['addressLocality']);
        $this->assertSame('NL', $schema['address']['addressCountry']);
    }

    public function testSchemaOrganizationIncludesLogoUrl(): void
    {
        $schema = StructuredData::schemaOrganization();
        $this->assertSame('https://test.example/assets/logo.png', $schema['logo']);
    }

    public function testSchemaOrganizationIncludesSocialLinks(): void
    {
        $schema = StructuredData::schemaOrganization();
        $this->assertIsArray($schema['sameAs']);
        $this->assertContains('https://linkedin.com/company/test', $schema['sameAs']);
        $this->assertContains('https://instagram.com/test', $schema['sameAs']);
    }

    public function testSchemaWebsiteHasCorrectTypeAndContext(): void
    {
        $schema = StructuredData::schemaWebsite();
        $this->assertSame('WebSite', $schema['@type']);
        $this->assertSame('https://schema.org', $schema['@context']);
    }

    public function testSchemaWebsiteIncludesUrl(): void
    {
        $schema = StructuredData::schemaWebsite();
        $this->assertStringStartsWith('https://test.example', $schema['url']);
    }

    public function testSchemaBreadcrumbsIncludesHome(): void
    {
        $schema = StructuredData::schemaBreadcrumbs('Page Title', '/page-title');
        $this->assertSame('BreadcrumbList', $schema['@type']);
        $items = $schema['itemListElement'];
        $this->assertNotEmpty($items);
        $this->assertSame('Home', $items[0]['name']);
        $this->assertSame(1, $items[0]['position']);
    }

    public function testSchemaBreadcrumbsIncludesCurrentPage(): void
    {
        $schema = StructuredData::schemaBreadcrumbs('Page Title', '/page-title');
        $items = $schema['itemListElement'];
        $lastItem = end($items);
        $this->assertSame('Page Title', $lastItem['name']);
        $this->assertSame('https://test.example/page-title', $lastItem['item']);
    }

    public function testSchemaBreadcrumbsWithParentsIncludesThem(): void
    {
        $schema = StructuredData::schemaBreadcrumbs('Child', '/parent/child', [
            ['name' => 'Parent', 'slug' => '/parent'],
        ]);
        $items = $schema['itemListElement'];
        $this->assertCount(3, $items); // Home + Parent + Child
        $this->assertSame('Home', $items[0]['name']);
        $this->assertSame('Parent', $items[1]['name']);
        $this->assertSame('Child', $items[2]['name']);
    }

    public function testSchemaBreadcrumbsPositionsCorrect(): void
    {
        $schema = StructuredData::schemaBreadcrumbs('Child', '/child', [
            ['name' => 'Parent', 'slug' => '/parent'],
        ]);
        $items = $schema['itemListElement'];
        $this->assertSame(1, $items[0]['position']);
        $this->assertSame(2, $items[1]['position']);
        $this->assertSame(3, $items[2]['position']);
    }

    public function testSchemaArticleReturnsCorrectType(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertSame('BlogPosting', $schema['@type']);
        $this->assertSame('https://schema.org', $schema['@context']);
    }

    public function testSchemaArticleIncludesHeadlineAndDescription(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertSame('Hello World', $schema['headline']);
        $this->assertSame('A short excerpt', $schema['description']);
    }

    public function testSchemaArticleIncludesAuthor(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertIsArray($schema['author']);
        $this->assertSame('Person', $schema['author']['@type']);
        $this->assertSame('Jane Doe', $schema['author']['name']);
    }

    public function testSchemaArticleIncludesPublisher(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertIsArray($schema['publisher']);
        $this->assertSame('Organization', $schema['publisher']['@type']);
        $this->assertSame('Test Company', $schema['publisher']['name']);
    }

    public function testSchemaArticleIncludesMainEntityOfPage(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertSame('WebPage', $schema['mainEntityOfPage']['@type']);
        $this->assertSame('https://test.example/blog/hello-world', $schema['mainEntityOfPage']['@id']);
    }

    public function testSchemaArticleIncludesSpeakable(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertIsArray($schema['speakable']);
        $this->assertSame('SpeakableSpecification', $schema['speakable']['@type']);
        $this->assertContains('h1', $schema['speakable']['cssSelector']);
    }

    public function testSchemaArticleWithoutAuthorUsesCompanyName(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertSame('Organization', $schema['author']['@type']);
        $this->assertSame('Test Company', $schema['author']['name']);
    }

    public function testSchemaArticleIncludesImage(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
            'afbeelding' => '/uploads/image.jpg',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertSame('https://test.example/uploads/image.jpg', $schema['image']);
    }

    public function testSchemaArticleIncludesPublisherLogo(): void
    {
        $post = [
            'titel' => 'Hello World',
            'slug' => 'hello-world',
            'samenvatting' => 'A short excerpt',
            'datum' => '2026-01-15',
            'auteur' => 'Jane Doe',
        ];
        $schema = StructuredData::schemaArticle($post);
        $this->assertIsArray($schema['publisher']['logo']);
        $this->assertSame('ImageObject', $schema['publisher']['logo']['@type']);
        $this->assertSame('https://test.example/assets/logo.png', $schema['publisher']['logo']['url']);
    }

    public function testRenderEmitsLdJsonScriptTag(): void
    {
        ob_start();
        StructuredData::render([
            ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Test'],
        ]);
        $output = (string) ob_get_clean();
        $this->assertStringContainsString('<script type="application/ld+json">', $output);
        $this->assertStringContainsString('"WebSite"', $output);
        $this->assertStringContainsString('</script>', $output);
    }

    public function testRenderWithMultipleSchemas(): void
    {
        ob_start();
        StructuredData::render([
            ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Org'],
            ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Site'],
        ]);
        $output = (string) ob_get_clean();
        $this->assertStringContainsString('"Organization"', $output);
        $this->assertStringContainsString('"WebSite"', $output);
    }

    public function testRenderSkipsEmptySchemas(): void
    {
        ob_start();
        StructuredData::render([
            [],
            ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Test'],
        ]);
        $output = (string) ob_get_clean();
        // Should only have one script tag
        $this->assertSame(1, substr_count($output, '<script'));
    }
}
