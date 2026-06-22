<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    protected $model = Credential::class;

    public function definition(): array
    {
        return [
            'subscription_id' => UserSubscription::factory(),
            'ministack_account_id' => '000000000000',
            'access_key_id' => 'AKIA' . $this->faker->lexify('????????'),
            'bucket_name' => 'bucket-' . $this->faker->word(),
            'secret_access_key' => encrypt('secret-key'),
            'status_kunci' => 'Aktif',
        ];
    }
}
