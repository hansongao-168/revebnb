<?php

namespace App\Site\Support;

use App\Models\SitePage;

class SitePageManifest
{
    public function sync(): void
    {
        foreach (config('site-pages', []) as $key => $definition) {
            SitePage::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'module_group' => $definition['module_group'],
                    'web_route_name' => $definition['web_route_name'],
                    'web_route_params' => $definition['web_route_params'] ?? [],
                    'uniapp_path' => $definition['uniapp_path'],
                    'description' => $definition['description'] ?? null,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
