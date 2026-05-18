<?php

namespace Database\Factories;

use App\Models\SiteNavigationItem;
use App\Models\SitePage;
use App\Site\Enums\SiteNavLinkType;
use App\Site\Enums\SiteNavPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteNavigationItem>
 */
class SiteNavigationItemFactory extends Factory
{
    protected $model = SiteNavigationItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement' => SiteNavPlacement::Header,
            'footer_group' => null,
            'title' => fake()->words(2, true),
            'link_type' => SiteNavLinkType::NamedRoute,
            'site_page_id' => null,
            'route_name' => 'site.stays.index',
            'route_params' => [],
            'external_url' => null,
            'icon' => null,
            'sort_order' => 0,
            'is_active' => true,
            'target' => '_self',
            'style_variant' => null,
            'active_match' => 'site.stays.*',
        ];
    }

    public function forPage(SitePage $page): static
    {
        return $this->state(fn () => [
            'link_type' => SiteNavLinkType::SitePage,
            'site_page_id' => $page->id,
            'route_name' => null,
            'route_params' => null,
            'external_url' => null,
        ]);
    }
}
