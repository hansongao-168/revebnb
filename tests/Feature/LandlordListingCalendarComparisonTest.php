<?php

namespace Tests\Feature;

use App\Filament\Landlord\Resources\Listings\Pages\ViewListingCalendarComparison;
use App\Models\ExternalCalendarEvent;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandlordListingCalendarComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('landlord');
        Filament::bootCurrentPanel();
    }

    public function test_landlord_can_view_calendar_comparison_for_own_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $other = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);

        $listing = Listing::factory()->forLandlord($landlord)->create();
        Listing::factory()->forLandlord($other)->create();

        $feed = ListingCalendarFeed::factory()->create(['listing_id' => $listing->id]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'summary' => 'Airbnb stay',
            'starts_at' => '2026-08-10',
            'ends_at' => '2026-08-12',
            'blocked_nights' => ['2026-08-10', '2026-08-11'],
        ]);

        $this->actingAs($landlord, 'landlord');

        Livewire::withQueryParams(['month' => '2026-08'])
            ->test(ViewListingCalendarComparison::class, ['record' => $listing->id])
            ->assertOk()
            ->assertSee('日历对比')
            ->assertSee('外部 ICS 订阅由平台')
            ->assertSee('Airbnb stay');
    }

    public function test_landlord_cannot_view_calendar_comparison_for_another_landlords_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlordA = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);
        $landlordB = Landlord::factory()->for($tenant)->create(['status' => Landlord::STATUS_ACTIVE]);

        $listingB = Listing::factory()->forLandlord($landlordB)->create();

        $this->actingAs($landlordA, 'landlord');

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ViewListingCalendarComparison::class, ['record' => $listingB->id]);
    }
}
