<?php

namespace Tests\Feature;

use App\Models\{Faq, SitePage};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_page_returns_null_so_the_storefront_keeps_its_default_text(): void
    {
        $this->getJson('/api/v1/pages/privacy')->assertOk()->assertJsonPath('data.content', null);
    }

    public function test_page_content_is_sanitised(): void
    {
        SitePage::where('slug', 'privacy')->update(['content' => '<p onclick="x()">Hi</p><script>alert(1)</script><a href="javascript:evil()">a</a>']);
        $html = $this->getJson('/api/v1/pages/privacy')->json('data.content');
        $this->assertStringContainsString('<p', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_inactive_faqs_are_hidden(): void
    {
        $all = count($this->getJson('/api/v1/faqs')->json('data'));
        Faq::first()->update(['is_active' => false]);
        $this->assertCount($all - 1, $this->getJson('/api/v1/faqs')->json('data'));
    }

    public function test_sitemap_data_lists_active_products_and_categories(): void
    {
        $this->seed();
        $d = $this->getJson('/api/v1/sitemap-data')->assertOk()->json('data');
        $this->assertNotEmpty($d['products']);
        $this->assertNotEmpty($d['categories']);
        $this->assertArrayHasKey('slug', $d['products'][0]);
    }
}
