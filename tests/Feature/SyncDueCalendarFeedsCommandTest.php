<?php

namespace Tests\Feature;

use App\Jobs\SyncListingCalendarFeedJob;
use App\Models\ListingCalendarFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SyncDueCalendarFeedsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_only_due_enabled_feeds(): void
    {
        Bus::fake();

        $due = ListingCalendarFeed::factory()->create([
            'is_enabled' => true,
            'last_synced_at' => now()->subHours(7),
            'sync_interval_hours' => 6,
        ]);

        ListingCalendarFeed::factory()->create([
            'is_enabled' => true,
            'last_synced_at' => now()->subHour(),
            'sync_interval_hours' => 6,
        ]);

        ListingCalendarFeed::factory()->disabled()->create([
            'last_synced_at' => now()->subDays(2),
        ]);

        $this->artisan('calendar-feeds:sync-due')->assertSuccessful();

        Bus::assertDispatched(SyncListingCalendarFeedJob::class, function (SyncListingCalendarFeedJob $job) use ($due): bool {
            return $job->feed->is($due);
        });

        Bus::assertDispatchedTimes(SyncListingCalendarFeedJob::class, 1);
    }
}
