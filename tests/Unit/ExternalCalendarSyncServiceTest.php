<?php

namespace Tests\Unit;

use App\Enums\CalendarFeedSyncStatus;
use App\Models\ExternalCalendarEvent;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Services\ExternalCalendarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalCalendarSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_upserts_events_and_removes_stale_uids(): void
    {
        $listing = Listing::factory()->create();
        $feed = ListingCalendarFeed::factory()->create([
            'listing_id' => $listing->id,
            'ical_url' => 'https://www.airbnb.fr/calendar/ical/test.ics?t=token',
        ]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'ical_uid' => 'stale-event',
            'blocked_nights' => ['2026-01-01'],
        ]);

        $ics = file_get_contents(base_path('tests/fixtures/ics/airbnb-sample.ics'));
        Http::fake([
            'www.airbnb.fr/*' => Http::response($ics, 200),
        ]);

        app(ExternalCalendarSyncService::class)->sync($feed->fresh());

        $feed->refresh();
        $this->assertSame(CalendarFeedSyncStatus::Success, $feed->last_sync_status);
        $this->assertNotNull($feed->last_successful_sync_at);
        $this->assertDatabaseMissing('external_calendar_events', ['ical_uid' => 'stale-event']);
        $this->assertDatabaseHas('external_calendar_events', ['ical_uid' => 'airbnb-reservation-sample-1']);
        $this->assertDatabaseHas('external_calendar_events', ['ical_uid' => 'airbnb-block-sample-2']);

        $event = ExternalCalendarEvent::query()
            ->where('ical_uid', 'airbnb-reservation-sample-1')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame(
            ['2026-08-10', '2026-08-11', '2026-08-12', '2026-08-13', '2026-08-14'],
            $event->blocked_nights,
        );
    }

    public function test_sync_failure_preserves_existing_events(): void
    {
        $feed = ListingCalendarFeed::factory()->create([
            'ical_url' => 'https://www.airbnb.fr/calendar/ical/test.ics?t=token',
        ]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'ical_uid' => 'keep-me',
        ]);

        Http::fake([
            'www.airbnb.fr/*' => Http::response('', 500),
        ]);

        try {
            app(ExternalCalendarSyncService::class)->sync($feed->fresh());
        } catch (\Throwable) {
            // expected
        }

        $feed->refresh();
        $this->assertSame(CalendarFeedSyncStatus::Failed, $feed->last_sync_status);
        $this->assertDatabaseHas('external_calendar_events', ['ical_uid' => 'keep-me']);
    }

    public function test_ical_url_is_encrypted_at_rest(): void
    {
        $url = 'https://www.airbnb.fr/calendar/ical/1542203908712660572.ics?t=secret-token';

        $feed = ListingCalendarFeed::factory()->create([
            'ical_url' => $url,
        ]);

        $raw = \DB::table('listing_calendar_feeds')->where('id', $feed->id)->value('ical_url');

        $this->assertNotSame($url, $raw);
        $this->assertSame($url, $feed->fresh()->ical_url);
    }
}
