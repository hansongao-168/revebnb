<?php

namespace Database\Factories;

use App\Enums\UnavailabilityBlockCreator;
use App\Models\Listing;
use App\Models\ListingUnavailabilityBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingUnavailabilityBlock>
 */
class ListingUnavailabilityBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'starts_on' => now()->addWeek()->toDateString(),
            'ends_on' => now()->addWeek()->toDateString(),
            'reason' => fake()->optional()->sentence(),
            'created_by_type' => UnavailabilityBlockCreator::Platform,
            'created_by_landlord_id' => null,
        ];
    }
}
