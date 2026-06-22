<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\Credential;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_checkout_requires_authentication(): void
    {
        $response = $this->postJson('/api/iaas/checkout', [
            'plan_id' => 1,
            'metode_bayar' => 'Transfer',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_checkout_requires_valid_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/iaas/checkout', [
            'plan_id' => 999,
            'metode_bayar' => 'Transfer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_api_checkout_creates_pending_payment(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/iaas/checkout', [
            'plan_id' => $plan->id,
            'metode_bayar' => 'Transfer',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', ['metode_bayar' => 'Transfer', 'status_bayar' => 'Pending']);
    }

    public function test_admin_unauthorized_to_verify_payment_via_api(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->patchJson("/api/admin/payments/{$payment->id}/verify");

        $response->assertStatus(403);
    }

    public function test_admin_can_verify_payment_via_api(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subscription = UserSubscription::factory()->create([
            'user_id' => $admin->id,
            'plan_id' => SubscriptionPlan::factory()->create(['is_active' => true])->id,
        ]);
        $payment = Payment::factory()->create([
            'status_bayar' => 'Pending',
            'subscription_id' => $subscription->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/admin/payments/{$payment->id}/verify");

        $response->assertStatus(200);
    }

    public function test_admin_can_toggle_credential_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $credential = Credential::factory()->create(['status_kunci' => 'Aktif']);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/admin/credentials/{$credential->id}/toggle");

        $response->assertStatus(200);
        $this->assertDatabaseHas('credentials', ['id' => $credential->id, 'status_kunci' => 'Dicabut']);
    }

    public function test_api_get_user_subscriptions_returns_data(): void
    {
        $user = User::factory()->create();
        $subscription = UserSubscription::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/iaas/subscriptions');

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_api_get_user_logs_returns_data_for_regular_user(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/iaas/logs');

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'data']);
    }

    public function test_api_get_user_logs_returns_all_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ActivityLog::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/iaas/logs');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
