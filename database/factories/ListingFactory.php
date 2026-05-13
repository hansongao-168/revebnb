<?php

namespace Database\Factories;

use App\Models\Landlord;
use App\Models\Listing;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'tenant_id' => null,
            'landlord_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'description' => implode("\n\n", fake()->paragraphs(2)),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'nightly_price' => fake()->randomFloat(2, 80, 800),
            'currency' => 'CNY',
            'status' => Listing::STATUS_DRAFT,
            'min_nights' => 1,
            'max_guests' => null,
            'guest_info_html' => null,
            'published_at' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forLandlord(Landlord $landlord): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $landlord->tenant_id,
            'landlord_id' => $landlord->id,
        ]);
    }
}
