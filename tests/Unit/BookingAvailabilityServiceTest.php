<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use App\Models\Tenant;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BookingAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_nights_half_open_range_returns_expected_nights(): void
    {
        $service = new BookingAvailabilityService;

        $nights = $service->bookingNightsInclusiveHalfOpen(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-04'),
        );

        $this->assertSame(['2026-06-01', '2026-06-02', '2026-06-03'], $nights);
    }

    public function test_block_nights_inclusive_closed_single_day_returns_one_night(): void
    {
        $service = new BookingAvailabilityService;

        $nights = $service->blockNightsInclusiveClosed(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-01'),
        );

        $this->assertSame(['2026-06-01'], $nights);
    }

    public function test_overlapping_confirmed_booking_fails_availability_assertion(): void
    {
        $service = new BookingAvailabilityService;

        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create(['min_nights' => 1]);

        Booking::factory()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-06-02',
            'check_out' => '2026-06-05',
            'status' => BookingStatus::Confirmed,
        ]);

        $candidate = Booking::factory()->make([
            'listing_id' => $listing->id,
            'check_in' => '2026-06-04',
            'check_out' => '2026-06-06',
            'status' => BookingStatus::Confirmed,
        ]);

        $this->expectException(ValidationException::class);

        $service->assertBookingAllowed($candidate);
    }

    public function test_confirmed_booking_overlapping_block_fails_availability_assertion(): void
    {
        $service = new BookingAvailabilityService;

        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create(['min_nights' => 1]);

        ListingUnavailabilityBlock::factory()->create([
            'listing_id' => $listing->id,
            'starts_on' => '2026-06-05',
            'ends_on' => '2026-06-07',
        ]);

        $candidate = Booking::factory()->make([
            'listing_id' => $listing->id,
            'check_in' => '2026-06-04',
            'check_out' => '2026-06-06',
            'status' => BookingStatus::Confirmed,
        ]);

        $this->expectException(ValidationException::class);

        $service->assertBookingAllowed($candidate);
    }

    public function test_block_overlapping_confirmed_booking_fails_block_assertion(): void
    {
        $service = new BookingAvailabilityService;

        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forLandlord($landlord)->create(['min_nights' => 1]);

        Booking::factory()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-06-10',
            'check_out' => '2026-06-13',
            'status' => BookingStatus::Confirmed,
        ]);

        $block = ListingUnavailabilityBlock::factory()->make([
            'listing_id' => $listing->id,
            'starts_on' => '2026-06-12',
            'ends_on' => '2026-06-15',
        ]);

        $this->expectException(ValidationException::class);

        $service->assertUnavailabilityBlockAllowed($block);
    }
}
