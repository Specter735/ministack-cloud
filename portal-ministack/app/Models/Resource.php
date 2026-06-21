<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Resource extends Model
{
    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'subscription_id',
        'kapasitas_storage'
    ];

    /**
     * Relasi balik ke tabel user_subscriptions (Buku Kontrak Sewa)
     * Fasilitas fisik dialokasikan berdasarkan satu kontrak penyewaan yang sah.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    /**
     * Relasi ke tabel buckets (Nama Brankas di Cloud)
     * Satu fasilitas (Resource) menaungi satu wadah penyimpanan (Bucket) di MiniStack.
     */
    public function bucket(): HasOne
    {
        return $this->hasOne(Bucket::class, 'resource_id');
    }
}