<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\Listings\Pages\ViewListingCalendarComparison;
use App\Models\ExternalCalendarEvent;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use App\Models\SaasUser;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantListingCalendarComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('tenant');
    }

    public function test_tenant_user_can_view_calendar_comparison_for_own_tenant_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $saasUser = SaasUser::factory()->for($tenant)->create();

        $landlord = Landlord::factory()->for($tenant)->create();
        $listing = Listing::factory()->forTenant($tenant)->forLandlord($landlord)->create();

        Listing::factory()->forTenant($otherTenant)->forLandlord(
            Landlord::factory()->for($otherTenant)->create(),
        )->create();

        $feed = ListingCalendarFeed::factory()->create(['listing_id' => $listing->id]);

        ExternalCalendarEvent::factory()->create([
            'listing_calendar_feed_id' => $feed->id,
            'summary' => 'Channel booking',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-09-03',
            'blocked_nights' => ['2026-09-01', '2026-09-02'],
        ]);

        $this->actingAs($saasUser, 'saas');

        Livewire::withQueryParams(['month' => '2026-09'])
            ->test(ViewListingCalendarComparison::class, ['record' => $listing->id])
            ->assertOk()
            ->assertSee('日历对比')
            ->assertSee('外部 ICS 订阅由平台')
            ->assertSee('Channel booking');
    }

    public function test_tenant_user_cannot_view_calendar_comparison_for_other_tenant_listing(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $saasUserA = SaasUser::factory()->for($tenantA)->create();
        $landlordB = Landlord::factory()->for($tenantB)->create();
        $listingB = Listing::factory()->forTenant($tenantB)->forLandlord($landlordB)->create();

        $this->actingAs($saasUserA, 'saas');

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(ViewListingCalendarComparison::class, ['record' => $listingB->id]);
    }
}
