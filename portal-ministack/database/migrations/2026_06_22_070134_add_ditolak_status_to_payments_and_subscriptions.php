<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah nilai 'Ditolak' pada ENUM status_bayar di tabel payments,
     * dan 'cancelled' pada ENUM status di tabel user_subscriptions.
     * Diperlukan untuk fitur Tolak Pembayaran oleh Administrator.
     */
    public function up(): void
    {
        // ENUM tidak bisa diubah via Blueprint::change() secara reliable di semua driver,
        // jadi kita pakai raw statement yang lebih aman dan eksplisit.
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status_bayar ENUM('Pending', 'Lunas', 'Ditolak') NOT NULL DEFAULT 'Pending'
        ");

        DB::statement("
            ALTER TABLE user_subscriptions
            MODIFY COLUMN status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // Rollback: hapus nilai baru (data yang sudah pakai nilai ini akan hilang)
        DB::statement("
            ALTER TABLE payments
            MODIFY COLUMN status_bayar ENUM('Pending', 'Lunas') NOT NULL DEFAULT 'Pending'
        ");

        DB::statement("
            ALTER TABLE user_subscriptions
            MODIFY COLUMN status ENUM('pending', 'active', 'expired') NOT NULL DEFAULT 'pending'
        ");
    }
};