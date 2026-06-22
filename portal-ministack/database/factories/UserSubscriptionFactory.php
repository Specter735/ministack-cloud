<?php

namespace Database\Factories;

use App\Models\UserSubscription;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserSubscriptionFactory extends Factory
{
    protected $model = UserSubscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'subscribed_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'pending',
        ];
    }
}
