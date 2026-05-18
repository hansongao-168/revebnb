<?php

namespace Tests\Feature;

use App\Models\SiteNavigationItem;
use App\Site\Services\SiteNavigationService;
use App\Site\Support\SitePageManifest;
use Database\Seeders\SiteNavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_site_pages_is_idempotent(): void
    {
        $this->artisan('site:sync-pages')->assertSuccessful();
        $this->assertDatabaseCount('site_pages', count(config('site-pages')));

        $this->artisan('site:sync-pages')->assertSuccessful();
        $this->assertDatabaseCount('site_pages', count(config('site-pages')));
    }

    public function test_sync_updates_name_from_manifest(): void
    {
        $this->artisan('site:sync-pages')->assertSuccessful();

        $pages = require config_path('site-pages.php');
        $pages['stays.index']['name'] = '住宿首页（测试）';
        config(['site-pages' => $pages]);
        app(SitePageManifest::class)->sync();

        $this->assertDatabaseHas('site_pages', [
            'key' => 'stays.index',
            'name' => '住宿首页（测试）',
        ]);
    }

    public function test_stays_page_renders_seeded_header_links(): void
    {
        $this->seed(SiteNavigationSeeder::class);

        $this->get(route('site.stays.index'))
            ->assertOk()
            ->assertSee('住宿', false)
            ->assertSee('我的订单', false);
    }

    public function test_inactive_header_item_hidden(): void
    {
        $this->seed(SiteNavigationSeeder::class);

        SiteNavigationItem::query()
            ->where('title', '我的订单')
            ->update(['is_active' => false]);

        app(SiteNavigationService::class)->flushCache();

        $this->get(route('site.stays.index'))
            ->assertOk()
            ->assertDontSee('我的订单', false);
    }
}
