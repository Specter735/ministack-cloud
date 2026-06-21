<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (mass assignment).
     * Catatan: Anda perlu memastikan kolom 'user_id', 'action', dan 'description' 
     * ditambahkan ke dalam tabel activity_logs melalui file migrasi nantinya.
     */
    protected $fillable = [
        'user_id',
        'action',
        'description',
    ];

    /**
     * Relasi balik ke tabel users.
     * Setiap log aktivitas dilakukan oleh satu pengguna yang spesifik.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}