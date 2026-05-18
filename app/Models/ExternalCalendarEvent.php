<?php

namespace App\Models;

use Database\Factories\ExternalCalendarEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCalendarEvent extends Model
{
    /** @use HasFactory<ExternalCalendarEventFactory> */
    use HasFactory;

    protected $fillable = [
        'listing_calendar_feed_id',
        'ical_uid',
        'summary',
        'starts_at',
        'ends_at',
        'all_day',
        'blocked_nights',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
            'blocked_nights' => 'array',
        ];
    }

    /** @return BelongsTo<ListingCalendarFeed, $this> */
    public function feed(): BelongsTo
    {
        return $this->belongsTo(ListingCalendarFeed::class, 'listing_calendar_feed_id');
    }
}
