<?php

namespace App\Models;

use App\Enums\CalendarFeedSyncStatus;
use Database\Factories\ListingCalendarFeedFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListingCalendarFeed extends Model
{
    /** @use HasFactory<ListingCalendarFeedFactory> */
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'label',
        'source',
        'ical_url',
        'is_enabled',
        'sync_interval_hours',
        'last_synced_at',
        'last_successful_sync_at',
        'last_sync_status',
        'last_sync_error',
    ];

    protected function casts(): array
    {
        return [
            'ical_url' => 'encrypted',
            'is_enabled' => 'boolean',
            'sync_interval_hours' => 'integer',
            'last_synced_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
            'last_sync_status' => CalendarFeedSyncStatus::class,
        ];
    }

    public function effectiveSyncIntervalHours(): int
    {
        return $this->sync_interval_hours
            ?? (int) config('calendar_feeds.default_sync_interval_hours', 6);
    }

    public function isDueForSync(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->last_synced_at === null) {
            return true;
        }

        return $this->last_synced_at->lte(
            now()->subHours($this->effectiveSyncIntervalHours()),
        );
    }

    /** @return BelongsTo<Listing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** @return HasMany<ExternalCalendarEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ExternalCalendarEvent::class);
    }
}
