<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ExternalCalendarEvent;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Models\ListingUnavailabilityBlock;
use App\Models\Tenant;
use App\Services\BookingAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingAvailabilityServiceUnavailableNightsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function merged_unavailable_includes_confirmed_booking_and_block(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create();

        Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-07-01',
            'check_out' => '2026-07-04',
            'status' => BookingStatus::Confirmed,
            'guest_name' => 'A',
        ]);

        ListingUnavailabilityBlock::query()->create([
            'listing_id' => $listing->id,
            'starts_on' => '2026-07-10',
            'ends_on' => '2026-07-12',
            'reason' => 'x',
            'created_by_type' => 'landlord',
            'created_by_landlord_id' => $landlord->id,
        ]);

        $svc = new BookingAvailabilityService;
        $set = $svc->unavailableNightSetForSiteCalendar($listing->id);

        $this->assertArrayHasKey('2026-07-01', $set);
        $this->assertArrayHasKey('2026-07-02', $set);
        $this->assertArrayNotHasKey('2026-07-04', $set);
        $this->assertArrayHasKey('2026-07-10', $set);
        $this->assertArrayHasKey('2026-07-12', $set);
    }

    #[Test]
    public function external_calendar_nights_merge_when_feed_opted_in(): void
    {
        $listing = Listing::factory()->create();
        $feed = ListingCalendarFeed::factory()
            ->blocksSiteAvailability()
            ->create(['listing_id' => $listing->id]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'blocked_nights' => ['2026-09-05', '2026-09-06'],
        ]);

        $svc = new BookingAvailabilityService;
        $set = $svc->unavailableNightSetForSiteCalendar($listing->id);

        $this->assertArrayHasKey('2026-09-05', $set);
        $this->assertArrayHasKey('2026-09-06', $set);
    }

    #[Test]
    public function external_calendar_nights_ignored_when_feed_not_opted_in(): void
    {
        $listing = Listing::factory()->create();
        $feed = ListingCalendarFeed::factory()->create([
            'listing_id' => $listing->id,
            'merge_into_site_availability' => false,
        ]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'blocked_nights' => ['2026-09-05'],
        ]);

        $svc = new BookingAvailabilityService;
        $set = $svc->unavailableNightSetForSiteCalendar($listing->id);

        $this->assertArrayNotHasKey('2026-09-05', $set);
    }
}
