<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ExternalCalendarEvent;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteListingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_returns_json_for_published_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'title' => 'August Listing',
            'slug' => 'august-listing',
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'nightly_price' => 199.5,
            'min_nights' => 2,
            'max_guests' => 5,
        ]);

        Booking::factory()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-08-10',
            'check_out' => '2026-08-13',
            'status' => BookingStatus::Confirmed,
            'guest_name' => 'Confirmed Guest',
        ]);

        $response = $this->getJson(route('site.stays.availability', [
            'listing' => $listing,
            'month' => '2026-08',
        ]));

        $response->assertOk()
            ->assertJsonPath('listing.id', $listing->id)
            ->assertJsonPath('listing.slug', $listing->slug)
            ->assertJsonPath('listing.title', $listing->title)
            ->assertJsonPath('month', '2026-08')
            ->assertJsonPath('min_nights', $listing->min_nights)
            ->assertJsonPath('max_guests', $listing->max_guests)
            ->assertJsonPath('nightly_price', (string) $listing->nightly_price)
            ->assertJsonStructure([
                'listing' => ['id', 'slug', 'title'],
                'month',
                'blocked_nights',
                'min_nights',
                'max_guests',
                'nightly_price',
            ]);

        $blockedNights = $response->json('blocked_nights');

        $this->assertContains('2026-08-10', $blockedNights);
        $this->assertContains('2026-08-11', $blockedNights);
        $this->assertNotContains('2026-08-13', $blockedNights);
    }

    public function test_availability_includes_external_calendar_when_feed_merged(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'slug' => 'ics-merged-listing',
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $feed = ListingCalendarFeed::factory()
            ->blocksSiteAvailability()
            ->create(['listing_id' => $listing->id]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'blocked_nights' => ['2026-08-20', '2026-08-21'],
        ]);

        $response = $this->getJson(route('site.stays.availability', [
            'listing' => $listing,
            'month' => '2026-08',
        ]));

        $blockedNights = $response->json('blocked_nights');

        $this->assertContains('2026-08-20', $blockedNights);
        $this->assertContains('2026-08-21', $blockedNights);
    }

    public function test_availability_returns_404_for_draft_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_DRAFT,
            'slug' => 'draft-availability-listing',
        ]);

        $this->getJson(route('site.stays.availability', [
            'listing' => $listing,
            'month' => '2026-08',
        ]))->assertNotFound();
    }
}
