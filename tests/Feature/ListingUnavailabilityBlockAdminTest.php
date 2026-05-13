<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Filament\Resources\ListingUnavailabilityBlocks\Pages\CreateListingUnavailabilityBlock;
use App\Models\Booking;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingUnavailabilityBlockAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_unavailability_block(): void
    {
        $landlord = Landlord::factory()->create();
        $listing = Listing::factory()->forLandlord($landlord)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateListingUnavailabilityBlock::class)
            ->fillForm([
                'listing_id' => $listing->id,
                'starts_on' => '2026-08-10',
                'ends_on' => '2026-08-12',
                'reason' => '房屋检修',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('listing_unavailability_blocks', [
            'listing_id' => $listing->id,
            'starts_on' => '2026-08-10 00:00:00',
            'ends_on' => '2026-08-12 00:00:00',
            'reason' => '房屋检修',
            'created_by_type' => 'platform',
            'created_by_landlord_id' => null,
        ]);
    }

    public function test_creating_block_conflicting_with_confirmed_booking_returns_validation_error(): void
    {
        $landlord = Landlord::factory()->create();
        $listing = Listing::factory()->forLandlord($landlord)->create();

        Booking::factory()->create([
            'listing_id' => $listing->id,
            'check_in' => '2026-08-15',
            'check_out' => '2026-08-18',
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateListingUnavailabilityBlock::class)
            ->fillForm([
                'listing_id' => $listing->id,
                'starts_on' => '2026-08-16',
                'ends_on' => '2026-08-20',
                'reason' => '线下维护',
            ])
            ->call('create')
            ->assertHasErrors(['starts_on']);
    }
}
