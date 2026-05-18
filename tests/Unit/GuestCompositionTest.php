<?php

namespace Tests\Unit;

use App\Models\Listing;
use App\Support\GuestComposition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCompositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fits_listing_checks_each_capacity_type(): void
    {
        $listing = new Listing([
            'max_adults' => 4,
            'max_children' => 2,
            'max_infants' => 1,
            'max_pets' => 0,
            'max_guests' => 6,
        ]);

        $this->assertTrue((new GuestComposition(adults: 2, children: 1))->fitsListing($listing));
        $this->assertFalse((new GuestComposition(adults: 5))->fitsListing($listing));
        $this->assertFalse((new GuestComposition(adults: 2, children: 3))->fitsListing($listing));
        $this->assertFalse((new GuestComposition(adults: 2, pets: 1))->fitsListing($listing));
    }

    public function test_legacy_max_guests_used_when_max_adults_missing(): void
    {
        $listing = new Listing([
            'max_adults' => null,
            'max_children' => 0,
            'max_infants' => 0,
            'max_pets' => 0,
            'max_guests' => 3,
        ]);

        $this->assertTrue((new GuestComposition(adults: 2, children: 1))->fitsListing($listing));
        $this->assertFalse((new GuestComposition(adults: 2, children: 2))->fitsListing($listing));
    }

    public function test_to_booking_attributes_sets_guest_total(): void
    {
        $composition = new GuestComposition(adults: 2, children: 1, infants: 1, pets: 0);

        $this->assertSame([
            'guest_adults' => 2,
            'guest_children' => 1,
            'guest_infants' => 1,
            'guest_pets' => 0,
            'guests' => 3,
        ], $composition->toBookingAttributes());
    }
}
