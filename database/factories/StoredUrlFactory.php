<?php

namespace Database\Factories;

use App\Models\StoredUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoredUrl>
 */
class StoredUrlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'url' => fake()->url(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
