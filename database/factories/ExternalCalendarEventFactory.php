<?php

namespace Database\Factories;

use App\Models\ExternalCalendarEvent;
use App\Models\ListingCalendarFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCalendarEvent>
 */
class ExternalCalendarEventFactory extends Factory
{
    protected $model = ExternalCalendarEvent::class;

    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('+1 week', '+2 months');

        return [
            'listing_calendar_feed_id' => ListingCalendarFeed::factory(),
            'ical_uid' => fake()->uuid(),
            'summary' => 'Reserved',
            'starts_at' => $startsOn,
            'ends_at' => (clone $startsOn)->modify('+3 days'),
            'all_day' => true,
            'blocked_nights' => [],
        ];
    }
}
