<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\ListingCalendarFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingCalendarFeed>
 */
class ListingCalendarFeedFactory extends Factory
{
    protected $model = ListingCalendarFeed::class;

    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'label' => 'Airbnb',
            'source' => 'airbnb',
            'ical_url' => 'https://www.airbnb.fr/calendar/ical/'.fake()->numerify('################').'.ics?t='.fake()->uuid(),
            'is_enabled' => true,
            'sync_interval_hours' => null,
            'last_synced_at' => null,
            'last_successful_sync_at' => null,
            'last_sync_status' => null,
            'last_sync_error' => null,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => [
            'is_enabled' => false,
        ]);
    }
}
