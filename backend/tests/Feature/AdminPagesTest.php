<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opens every Filament admin page (resource lists, create forms, custom pages)
 * as a logged-in admin and reports ALL pages that fail, not just the first one.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_renders(): void
    {
        $this->seed();
        $admin = AdminUser::first();
        $this->actingAs($admin, 'admin');

        $panel = Filament::getPanel('admin');
        $urls = [];

        foreach ($panel->getResources() as $resource) {
            foreach (['index', 'create'] as $page) {
                if (array_key_exists($page, $resource::getPages())) {
                    $urls["{$resource}@{$page}"] = $resource::getUrl($page, panel: 'admin');
                }
            }
        }
        foreach ($panel->getPages() as $page) {
            $urls[$page] = $page::getUrl(panel: 'admin');
        }

        $failures = [];
        foreach ($urls as $label => $url) {
            try {
                $response = $this->get($url);
                if ($response->getStatusCode() >= 400) {
                    $failures[] = "{$label} → HTTP {$response->getStatusCode()}  ({$url})";
                }
            } catch (\Throwable $e) {
                $failures[] = "{$label} → " . get_class($e) . ': ' . substr($e->getMessage(), 0, 160);
            }
        }

        $this->assertSame([], $failures, "Admin pages failing:\n" . implode("\n", $failures));
    }
}
