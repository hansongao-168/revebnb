<?php

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SitePage>
 */
class SitePageFactory extends Factory
{
    protected $model = SitePage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'stays.index',
            'name' => '住宿列表',
            'module_group' => 'stays',
            'web_route_name' => 'site.stays.index',
            'web_route_params' => [],
            'uniapp_path' => null,
            'description' => null,
            'is_system' => true,
            'is_active' => true,
        ];
    }
}
