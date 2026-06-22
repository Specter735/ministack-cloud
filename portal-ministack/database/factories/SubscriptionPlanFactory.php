<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10000, 200000),
            'storage_quota_gb' => $this->faker->numberBetween(1, 200),
            'max_buckets' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
