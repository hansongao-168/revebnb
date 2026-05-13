<?php

namespace Tests\Feature;

use App\Filament\Resources\Listings\Pages\CreateListing;
use App\Models\Landlord;
use App\Models\Listing;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $landlord = Landlord::factory()->for($tenant)->create();

        $this->actingAs(User::factory()->admin()->create());

        $slug = 'listing-'.uniqid();

        Livewire::test(CreateListing::class)
            ->fillForm([
                'tenant_id' => $tenant->id,
                'landlord_id' => $landlord->id,
                'title' => '海景一居室',
                'slug' => $slug,
                'status' => Listing::STATUS_PUBLISHED,
                'description' => '近地铁，拎包入住。',
                'min_nights' => 2,
                'max_guests' => null,
                'city' => '上海',
                'address' => '浦东新区示例路 1 号',
                'nightly_price' => 299,
                'currency' => 'CNY',
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('listings', [
            'tenant_id' => $tenant->id,
            'landlord_id' => $landlord->id,
            'slug' => $slug,
            'title' => '海景一居室',
            'status' => Listing::STATUS_PUBLISHED,
            'min_nights' => 2,
        ]);
    }
}
