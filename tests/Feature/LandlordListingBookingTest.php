<?php

namespace Tests\Feature;

use App\Filament\Landlord\Resources\Listings\Pages\ListListings;
use App\Filament\Landlord\Resources\ListingUnavailabilityBlocks\Pages\CreateListingUnavailabilityBlock;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use App\Models\Tenant;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandlordListingBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landlord_listing_table_only_shows_own_listings(): void
    {
        $tenant = Tenant::factory()->create();
        $landlordA = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $landlordB = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);

        $listingA = Listing::factory()->forLandlord($landlordA)->create();
        $listingB = Listing::factory()->forLandlord($landlordB)->create();

        Filament::setCurrentPanel('landlord');
        Filament::bootCurrentPanel();
        $this->actingAs($landlordA, 'landlord');

        $this->assertNotNull(auth('landlord')->user());
        $this->assertNotNull(Filament::auth()->user());

        Livewire::test(ListListings::class)
            ->assertCanSeeTableRecords([$listingA])
            ->assertCanNotSeeTableRecords([$listingB]);
    }

    public function test_landlord_can_create_unavailability_block_for_own_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $listing = Listing::factory()->forLandlord($landlord)->create();

        $start = Carbon::now()->addDays(10)->toDateString();
        $end = Carbon::now()->addDays(12)->toDateString();

        Filament::setCurrentPanel('landlord');
        Filament::bootCurrentPanel();
        $this->actingAs($landlord, 'landlord');

        Livewire::test(CreateListingUnavailabilityBlock::class)
            ->fillForm([
                'listing_id' => $listing->id,
                'starts_on' => $start,
                'ends_on' => $end,
                'reason' => '维修',
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertTrue(
            ListingUnavailabilityBlock::query()
                ->where('listing_id', $listing->id)
                ->whereDate('starts_on', $start)
                ->whereDate('ends_on', $end)
                ->exists()
        );
    }
}
