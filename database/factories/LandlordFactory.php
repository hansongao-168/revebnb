<?php

namespace Database\Factories;

use App\Models\Landlord;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Landlord>
 */
class LandlordFactory extends Factory
{
    protected $model = Landlord::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'status' => Landlord::STATUS_ACTIVE,
            'password' => Str::random(40),
        ];
    }
}
