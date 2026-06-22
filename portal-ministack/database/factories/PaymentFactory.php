<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'subscription_id' => UserSubscription::factory(),
            'metode_bayar' => $this->faker->randomElement(['Transfer Bank', 'E-wallet', 'COD']),
            'status_bayar' => 'Pending',
        ];
    }
}
