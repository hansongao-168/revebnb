<?php

namespace App\Site\Services;

use App\Models\SiteNavigationItem;
use App\Models\SitePage;
use App\Site\Data\ResolvedNavItem;
use App\Site\Enums\SiteNavPlacement;
use App\Site\Support\SiteNavigationDefaults;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class SiteNavigationService
{
    public function __construct(
        private readonly SiteNavigationResolver $resolver,
    ) {}

    /**
     * @return Collection<int, ResolvedNavItem>
     */
    public function forPlacement(SiteNavPlacement $placement, ?string $footerGroup = null): Collection
    {
        return $this->loadPlacement($placement, $footerGroup);
    }

    public function flushCache(): void
    {
        // Navigation is resolved per request (active state + readonly DTOs are not cache-safe).
    }

    /**
     * @return Collection<int, ResolvedNavItem>
     */
    private function loadPlacement(SiteNavPlacement $placement, ?string $footerGroup): Collection
    {
        $query = SiteNavigationItem::query()
            ->where('placement', $placement)
            ->where('is_active', true)
            ->orderBy('sort_order');

        if ($footerGroup !== null) {
            $query->where('footer_group', $footerGroup);
        }

        $items = $query->with('sitePage')->get();

        if ($items->isEmpty()) {
            $items = $this->defaultModels($placement, $footerGroup);
        }

        $currentRoute = Route::currentRouteName();

        return $items
            ->map(fn (SiteNavigationItem $item) => $this->resolver->resolve($item, $currentRoute))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, SiteNavigationItem>
     */
    private function defaultModels(SiteNavPlacement $placement, ?string $footerGroup): Collection
    {
        return collect(SiteNavigationDefaults::items())
            ->filter(function (array $row) use ($placement, $footerGroup): bool {
                if ($row['placement'] !== $placement) {
                    return false;
                }

                if ($placement === SiteNavPlacement::Footer) {
                    return $row['footer_group'] === $footerGroup;
                }

                return true;
            })
            ->map(function (array $row): SiteNavigationItem {
                $item = new SiteNavigationItem([
                    'placement' => $row['placement'],
                    'footer_group' => $row['footer_group'],
                    'title' => $row['title'],
                    'link_type' => $row['link_type'],
                    'route_name' => $row['route_name'],
                    'route_params' => $row['route_params'],
                    'external_url' => $row['external_url'],
                    'icon' => $row['icon'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => $row['is_active'],
                    'target' => $row['target'],
                    'style_variant' => $row['style_variant'],
                    'active_match' => $row['active_match'],
                ]);

                if (! empty($row['site_page_key'])) {
                    $page = SitePage::query()->where('key', $row['site_page_key'])->first();
                    if ($page !== null) {
                        $item->site_page_id = $page->id;
                        $item->setRelation('sitePage', $page);
                    }
                }

                return $item;
            })
            ->values();
    }
}
