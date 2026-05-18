<?php

namespace Database\Seeders;

use App\Models\SiteNavigationItem;
use App\Models\SitePage;
use App\Site\Support\SiteNavigationDefaults;
use App\Site\Support\SitePageManifest;
use Illuminate\Database\Seeder;

class SiteNavigationSeeder extends Seeder
{
    public function run(): void
    {
        app(SitePageManifest::class)->sync();

        SiteNavigationItem::query()->delete();

        $pagesByKey = SitePage::query()->pluck('id', 'key');

        foreach (SiteNavigationDefaults::items() as $row) {
            $sitePageId = null;

            if (! empty($row['site_page_key'])) {
                $sitePageId = $pagesByKey[$row['site_page_key']] ?? null;
            }

            SiteNavigationItem::query()->create([
                'placement' => $row['placement'],
                'footer_group' => $row['footer_group'],
                'title' => $row['title'],
                'link_type' => $row['link_type'],
                'site_page_id' => $sitePageId,
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
        }
    }
}
