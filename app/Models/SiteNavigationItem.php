<?php

namespace App\Models;

use App\Site\Enums\SiteNavLinkType;
use App\Site\Enums\SiteNavPlacement;
use Database\Factories\SiteNavigationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteNavigationItem extends Model
{
    /** @use HasFactory<SiteNavigationItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'placement',
        'footer_group',
        'title',
        'link_type',
        'site_page_id',
        'route_name',
        'route_params',
        'external_url',
        'icon',
        'sort_order',
        'is_active',
        'target',
        'style_variant',
        'active_match',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placement' => SiteNavPlacement::class,
            'link_type' => SiteNavLinkType::class,
            'route_params' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function sitePage(): BelongsTo
    {
        return $this->belongsTo(SitePage::class);
    }
}
