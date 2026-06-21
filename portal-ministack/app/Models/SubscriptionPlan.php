<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (mass assignment).
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'storage_quota_gb',
        'max_buckets',
        'is_active',
    ];

    /**
     * Pengecoran tipe data (type casting) pada kolom spesifik.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke tabel user_subscriptions (Buku Kontrak Sewa).
     * Satu tipe paket dapat disewa oleh banyak pengguna.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }
}