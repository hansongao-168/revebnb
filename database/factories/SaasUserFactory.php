<?php

namespace Database\Factories;

use App\Models\SaasUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaasUser>
 */
class SaasUserFactory extends Factory
{
    protected $model = SaasUser::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => 'owner',
            'status' => 1,
        ];
    }
}
