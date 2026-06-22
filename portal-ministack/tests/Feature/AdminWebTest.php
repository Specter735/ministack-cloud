<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_index_shows_pending_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payment = Payment::factory()->create(['status_bayar' => 'Pending']);

        $response = $this->actingAs($admin)->get('/admin/payments');

        $response->assertStatus(200);
        $response->assertViewHas('payments');
    }

    public function test_admin_verify_route_redirects_with_success(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $payment = Payment::factory()->create(['status_bayar' => 'Pending']);

        $response = $this->actingAs($admin)->post("/admin/payments/{$payment->id}/verify");

        $response->assertRedirect('/admin/payments');
    }

    public function test_admin_cannot_access_admin_page_if_not_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/payments');

        $response->assertStatus(403);
    }

    public function test_admin_page_displays_pending_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Payment::factory()->count(3)->create(['status_bayar' => 'Pending']);

        $response = $this->actingAs($admin)->get('/admin/payments');

        $response->assertStatus(200);
        $response->assertViewHas('pendingCount');
    }

    public function test_admin_can_filter_payments_by_all_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Payment::factory()->create(['status_bayar' => 'Lunas']);

        $response = $this->actingAs($admin)->get('/admin/payments?status=all');

        $response->assertStatus(200);
        $response->assertViewHas('payments');
    }

    public function test_admin_verify_fails_if_payment_missing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/payments/999/verify');

        $response->assertStatus(404);
    }

    public function test_admin_access_order_page_returns_403_for_guest(): void
    {
        $response = $this->get('/admin/payments');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_payment_index_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/payments');

        $response->assertStatus(200);
    }

    public function test_admin_verify_route_disallows_non_admins(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create(['status_bayar' => 'Pending']);

        $response = $this->actingAs($user)->post("/admin/payments/{$payment->id}/verify");

        $response->assertStatus(403);
    }
}
