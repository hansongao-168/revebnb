<?php

namespace Tests\Feature;

use App\Filament\Tenant\Resources\Listings\Pages\ListListings;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\SaasUser;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantListingBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_listing_table_only_shows_current_tenant_records(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $saasUser = SaasUser::factory()->for($tenant)->create();

        $landlord = Landlord::factory()->for($tenant)->create();
        $otherLandlord = Landlord::factory()->for($otherTenant)->create();

        $tenantListing = Listing::factory()->forTenant($tenant)->forLandlord($landlord)->create();
        $otherTenantListing = Listing::factory()->forTenant($otherTenant)->forLandlord($otherLandlord)->create();

        $this->actingAs($saasUser, 'saas');
        Filament::setCurrentPanel('tenant');

        Livewire::test(ListListings::class)
            ->assertSee($tenantListing->title)
            ->assertDontSee($otherTenantListing->title);
    }

    public function test_saas_user_cannot_access_admin_listing_unavailability_block_index(): void
    {
        $tenant = Tenant::factory()->create();
        $saasUser = SaasUser::factory()->for($tenant)->create();

        $this->actingAs($saasUser, 'saas');

        $response = $this->get(route('filament.admin.resources.listing-unavailability-blocks.index'));

        $response->assertRedirect(route('filament.admin.auth.login'));
        $this->assertFalse(class_exists('App\\Filament\\Tenant\\Resources\\ListingUnavailabilityBlocks\\ListingUnavailabilityBlockResource'));
    }
}
