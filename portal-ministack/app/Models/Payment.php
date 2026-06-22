<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'subscription_id',
        'metode_bayar',
        'status_bayar'
    ];

    /**
     * Relasi balik ke tabel user_subscriptions (Buku Kontrak Sewa)
     * Setiap nota pembayaran terikat pada satu kontrak penyewaan.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }
}