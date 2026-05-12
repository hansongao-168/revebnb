<?php

namespace Tests\Feature;

use App\Filament\Resources\Listings\Pages\CreateListing;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_listing(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $slug = 'listing-'.uniqid();

        Livewire::test(CreateListing::class)
            ->fillForm([
                'tenant_id' => null,
                'title' => '海景一居室',
                'slug' => $slug,
                'status' => Listing::STATUS_PUBLISHED,
                'description' => '近地铁，拎包入住。',
                'city' => '上海',
                'address' => '浦东新区示例路 1 号',
                'nightly_price' => 299,
                'currency' => 'CNY',
                'cover_image' => null,
                'published_at' => null,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('listings', [
            'slug' => $slug,
            'title' => '海景一居室',
            'status' => Listing::STATUS_PUBLISHED,
        ]);
    }
}
