<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pembuatan akun admin untuk keperluan pengujian API (Administrator)
        User::create([
            'name' => 'Admin Test',
            'email' => 'admin@ministack.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Penambahan 3 Paket Layanan IaaS dengan tema Candy Pop
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Spark Plan',
                'description' => 'Paket pemula bertema ceria untuk penyimpanan data personal.',
                'price' => 50000.00,
                'storage_quota_gb' => 10,
                'max_buckets' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'Surge Plan',
                'description' => 'Paket menengah untuk kolaborasi tim dengan kapasitas penyimpanan ekstra.',
                'price' => 150000.00,
                'storage_quota_gb' => 50,
                'max_buckets' => 3,
                'is_active' => 1,
            ],
            [
                'name' => 'Candy Burst Plan',
                'description' => 'Paket premium IaaS tanpa batasan ketat untuk kebutuhan tingkat enterprise.',
                'price' => 350000.00,
                'storage_quota_gb' => 200,
                'max_buckets' => 10,
                'is_active' => 1,
            ],
        ]);
    }
}