<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
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
}
