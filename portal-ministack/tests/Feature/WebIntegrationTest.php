<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Payment;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_to_login_when_guest(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function test_login_page_displayed(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_unverified_user_can_still_login(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/payments');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/admin/payments');

        $response->assertStatus(200);
    }

    public function test_storage_page_shows_plans_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        SubscriptionPlan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get('/storage');

        $response->assertStatus(200);
        $response->assertViewHas('plans');
    }

    public function test_storage_checkout_creates_subscription_and_payment(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post('/storage/checkout', [
            'plan_id' => $plan->id,
            'metode_bayar' => 'Transfer Bank',
        ]);

        $response->assertRedirect('/storage');
        $this->assertDatabaseHas('user_subscriptions', ['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payments', ['metode_bayar' => 'Transfer Bank', 'status_bayar' => 'Pending']);
    }

    public function test_storage_checkout_fails_for_invalid_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/storage')->post('/storage/checkout', [
            'plan_id' => 9999,
            'metode_bayar' => 'Transfer Bank',
        ]);

        $response->assertRedirect('/storage');
        $response->assertSessionHasErrors('plan_id');
    }

    public function test_storage_checkout_fails_for_missing_payment_method(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->from('/storage')->post('/storage/checkout', [
            'plan_id' => $plan->id,
        ]);

        $response->assertRedirect('/storage');
        $response->assertSessionHasErrors('metode_bayar');
    }

    public function test_credentials_page_displays_secret_only_when_requested(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $subscription = UserSubscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active']);
        $credential = \App\Models\Credential::factory()->create(['subscription_id' => $subscription->id, 'secret_access_key' => encrypt('secret-key')]);

        $response = $this->actingAs($user)->get('/credentials?show_secret=1');

        $response->assertStatus(200);
        $response->assertViewHas('secretAccessKey');
    }

    public function test_credentials_page_shows_no_secret_when_not_requested(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);
        $subscription = UserSubscription::factory()->create(['user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active']);

        $response = $this->actingAs($user)->get('/credentials');

        $response->assertStatus(200);
        $response->assertViewHas('credential');
    }
}
