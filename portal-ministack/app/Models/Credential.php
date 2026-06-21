<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'subscription_id',
        'ministack_account_id',
        'access_key_id',
        'bucket_name',
        'secret_access_key',
        'status_kunci',
    ];

    /**
     * Relasi balik ke tabel user_subscriptions (Buku Kontrak Sewa)
     * Kunci akses terikat secara eksklusif pada satu kontrak penyewaan.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }
}