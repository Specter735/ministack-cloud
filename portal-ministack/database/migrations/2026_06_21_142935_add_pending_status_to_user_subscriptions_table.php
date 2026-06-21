<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE user_subscriptions MODIFY status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE user_subscriptions SET status = 'cancelled' WHERE status = 'pending'");
        DB::statement("ALTER TABLE user_subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
    }
};