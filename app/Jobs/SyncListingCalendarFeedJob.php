<?php

namespace App\Jobs;

use App\Models\ListingCalendarFeed;
use App\Services\ExternalCalendarSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncListingCalendarFeedJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public ListingCalendarFeed $feed) {}

    public function uniqueId(): string
    {
        return 'calendar-feed:'.$this->feed->id;
    }

    public function handle(ExternalCalendarSyncService $syncService): void
    {
        $this->feed->refresh();

        if (! $this->feed->is_enabled) {
            return;
        }

        $syncService->sync($this->feed);
    }
}
