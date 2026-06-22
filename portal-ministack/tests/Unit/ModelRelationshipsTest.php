<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use App\Models\Credential;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_subscriptions_relation(): void
    {
        $user = User::factory()->create();
        $subscription = UserSubscription::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->subscriptions->contains($subscription));
    }

    public function test_subscription_relationships_are_resolved(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = UserSubscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id]);
        $payment = Payment::factory()->create(['subscription_id' => $subscription->id]);
        Credential::factory()->create(['subscription_id' => $subscription->id]);

        $this->assertSame($user->id, $subscription->user->id);
        $this->assertSame($plan->id, $subscription->plan->id);
        $this->assertSame($subscription->id, $subscription->payment->subscription_id);
        $this->assertSame($subscription->id, $subscription->credential->subscription_id);
    }

    public function test_payment_belongs_to_subscription(): void
    {
        $subscription = UserSubscription::factory()->create();
        $payment = Payment::factory()->create(['subscription_id' => $subscription->id]);

        $this->assertSame($subscription->id, $payment->subscription->id);
    }

    public function test_credential_belongs_to_subscription(): void
    {
        $subscription = UserSubscription::factory()->create();
        $credential = Credential::factory()->create(['subscription_id' => $subscription->id]);

        $this->assertSame($subscription->id, $credential->subscription->id);
    }

    public function test_activity_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create(['user_id' => $user->id]);

        $this->assertSame($user->id, $log->user->id);
    }
}
