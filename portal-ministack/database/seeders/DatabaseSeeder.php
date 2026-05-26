<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // akun admin untuk testing
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@ministack.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // 3 Paket Layanan ke tabel subscription plans
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Basic',
                'description' => 'Paket dasar untuk pengguna baru',
                'price' => 50000.00,
                'storage_quota_gb' => 10,
                'max_buckets' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Standard',
                'description' => 'Paket menengah dengan lebih banyak storage',
                'price' => 150000.00,
                'storage_quota_gb' => 50,
                'max_buckets' => 3,
                'is_active' => 1,
            ],
            [
                'name' => 'Pro',
                'description' => 'Paket profesional tanpa batasan ketat',
                'price' => 350000.00,
                'storage_quota_gb' => 200,
                'max_buckets' => 10,
                'is_active' => 1,
            ],
        ]);
    }
}