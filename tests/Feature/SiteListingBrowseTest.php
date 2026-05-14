<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteListingBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_stays(): void
    {
        $this->get('/')->assertRedirect('/stays');
    }

    public function test_browse_page_shows_only_published_listings(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $published = Listing::factory()->forLandlord($landlord)->create([
            'title' => '上海武康路藤蔓老公寓',
            'city' => '上海',
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $draft = Listing::factory()->forLandlord($landlord)->create([
            'title' => '草稿房源不应可见',
            'status' => Listing::STATUS_DRAFT,
        ]);

        $response = $this->get(route('site.stays.index'));

        $response->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);
    }

    public function test_browse_page_filters_by_destination_and_guests(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $match = Listing::factory()->forLandlord($landlord)->create([
            'title' => '杭州西溪湿地畔住所',
            'city' => '杭州',
            'max_guests' => 4,
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $tooSmall = Listing::factory()->forLandlord($landlord)->create([
            'title' => '上海仅容两人的小公寓',
            'city' => '上海',
            'max_guests' => 2,
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('site.stays.index', [
            'destination' => '杭州',
            'guests' => 4,
        ]));

        $response->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($tooSmall->title);
    }

    public function test_show_page_returns_404_for_unpublished_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_DRAFT,
            'slug' => 'draft-private-place',
        ]);

        $this->get(route('site.stays.show', $listing))->assertNotFound();
    }

    public function test_booking_inquiry_creates_pending_booking(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'min_nights' => 1,
        ]);

        $checkIn = now()->addDays(5)->toDateString();
        $checkOut = now()->addDays(8)->toDateString();

        $response = $this->post(route('site.bookings.store', $listing), [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
            'guest_name' => 'Mia',
            'notes' => '希望晚一点 check-in',
        ]);

        $response->assertRedirect(route('site.stays.show', $listing));
        $response->assertSessionHas('booking_inquiry_success', true);

        $booking = Booking::query()->where('listing_id', $listing->id)->latest('id')->first();
        $this->assertNotNull($booking);
        $this->assertSame('Mia', $booking->guest_name);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($checkIn, $booking->check_in->toDateString());
        $this->assertSame($checkOut, $booking->check_out->toDateString());
    }

    public function test_booking_inquiry_rejects_conflict_with_confirmed_booking(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'min_nights' => 1,
        ]);

        Booking::query()->create([
            'listing_id' => $listing->id,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(9)->toDateString(),
            'status' => BookingStatus::Confirmed,
            'guest_name' => 'Existing Guest',
        ]);

        $response = $this->from(route('site.stays.show', $listing))
            ->post(route('site.bookings.store', $listing), [
                'check_in' => now()->addDays(6)->toDateString(),
                'check_out' => now()->addDays(8)->toDateString(),
                'guests' => 2,
                'guest_name' => 'Late Bird',
            ]);

        $response->assertRedirect(route('site.stays.show', $listing));
        $response->assertSessionHasErrors('check_in');

        $this->assertDatabaseMissing('bookings', [
            'listing_id' => $listing->id,
            'guest_name' => 'Late Bird',
        ]);
    }

    public function test_booking_inquiry_rejects_dates_inside_unavailability_block(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $listing = Listing::factory()->forLandlord($landlord)->create([
            'status' => Listing::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'min_nights' => 1,
        ]);

        ListingUnavailabilityBlock::query()->create([
            'listing_id' => $listing->id,
            'starts_on' => now()->addDays(5)->toDateString(),
            'ends_on' => now()->addDays(8)->toDateString(),
            'reason' => 'maintenance',
            'created_by_type' => 'landlord',
            'created_by_landlord_id' => $landlord->id,
        ]);

        $response = $this->from(route('site.stays.show', $listing))
            ->post(route('site.bookings.store', $listing), [
                'check_in' => now()->addDays(6)->toDateString(),
                'check_out' => now()->addDays(7)->toDateString(),
                'guests' => 2,
                'guest_name' => 'Blocked Bird',
            ]);

        $response->assertRedirect(route('site.stays.show', $listing));
        $response->assertSessionHasErrors('check_in');
    }
}
