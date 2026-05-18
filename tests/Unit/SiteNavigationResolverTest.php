<?php

namespace Tests\Unit;

use App\Models\SiteNavigationItem;
use App\Models\SitePage;
use App\Site\Enums\SiteNavLinkType;
use App\Site\Services\SiteNavigationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNavigationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_site_page_link(): void
    {
        $page = SitePage::factory()->create([
            'key' => 'stays.index',
            'web_route_name' => 'site.stays.index',
            'web_route_params' => ['category' => 'design'],
        ]);

        $item = SiteNavigationItem::factory()->forPage($page)->create([
            'link_type' => SiteNavLinkType::SitePage,
        ]);

        $resolved = app(SiteNavigationResolver::class)->resolve($item);

        $this->assertNotNull($resolved);
        $this->assertStringContainsString('/stays', $resolved->url ?? $resolved->href());
        $this->assertStringContainsString('category=design', $resolved->url ?? $resolved->href());
    }

    public function test_skips_invalid_named_route(): void
    {
        $item = SiteNavigationItem::factory()->create([
            'link_type' => SiteNavLinkType::NamedRoute,
            'route_name' => 'route.that.does.not.exist',
        ]);

        $resolved = app(SiteNavigationResolver::class)->resolve($item);

        $this->assertNull($resolved);
    }

    public function test_active_match_wildcard(): void
    {
        $resolver = app(SiteNavigationResolver::class);

        $this->assertTrue($resolver->matchesActive('site.stays.index', 'site.stays.*'));
        $this->assertFalse($resolver->matchesActive('site.me.bookings', 'site.stays.*'));
    }
}
