<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
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
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'status' => BookingStatus::Draft,
            'guests' => null,
            'guest_name' => null,
            'guest_email' => null,
            'guest_access_token_hash' => null,
            'guest_access_token_expires_at' => null,
            'notes' => null,
        ];
    }
}
