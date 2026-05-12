<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numerify('####')),
            'status' => Tenant::STATUS_TRIAL,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'notes' => null,
            'plan' => null,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
        ];
    }
}
