<?php

namespace App\Models;

use Database\Factories\UniappNavigationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniappNavigationItem extends Model
{
    /** @use HasFactory<UniappNavigationItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'placement',
        'title',
        'link_type',
        'site_page_id',
        'path',
        'external_url',
        'icon',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function sitePage(): BelongsTo
    {
        return $this->belongsTo(SitePage::class);
    }
}
