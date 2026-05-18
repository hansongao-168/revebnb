<?php

namespace App\Console\Commands;

use App\Jobs\SyncListingCalendarFeedJob;
use App\Models\ListingCalendarFeed;
use Illuminate\Console\Command;

class SyncDueCalendarFeedsCommand extends Command
{
    protected $signature = 'calendar-feeds:sync-due';

    protected $description = 'Dispatch sync jobs for external calendar feeds that are due';

    public function handle(): int
    {
        $dispatched = 0;

        ListingCalendarFeed::query()
            ->where('is_enabled', true)
            ->orderBy('id')
            ->each(function (ListingCalendarFeed $feed) use (&$dispatched): void {
                if (! $feed->isDueForSync()) {
                    return;
                }

                SyncListingCalendarFeedJob::dispatch($feed);
                $dispatched++;
            });

        $this->info("Dispatched {$dispatched} calendar feed sync job(s).");

        return self::SUCCESS;
    }
}
