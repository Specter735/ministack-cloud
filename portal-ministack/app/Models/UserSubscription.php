<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'subscribed_at',
        'expires_at',
        'status'
    ];

    /**
     * Relasi ke tabel users (Pemilik Kontrak)
     * Ketiadaan fungsi ini yang sebelumnya memicu galat "Attempt to read property 'name' on null".
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke tabel subscription_plans (Katalog Paket)
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    /**
     * Relasi ke tabel payments (Nota Pembayaran)
     * Satu kontrak sewa memiliki satu data pembayaran.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'subscription_id');
    }

    /**
     * Relasi ke tabel resources (Fasilitas Penyimpanan)
     * Satu kontrak sewa memiliki satu alokasi fasilitas fisik.
     */
    public function resource(): HasOne
    {
        return $this->hasOne(Resource::class, 'subscription_id');
    }

    /**
     * Relasi ke tabel credentials (Kunci Akses Rahasia)
     * Satu kontrak sewa memiliki satu set kunci IaaS.
     */
    public function credential(): HasOne
    {
        return $this->hasOne(Credential::class, 'subscription_id');
    }
}