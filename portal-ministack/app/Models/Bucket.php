<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bucket extends Model
{
    use HasFactory;

    /**
     * Mendefinisikan kolom yang diizinkan untuk diisi secara massal (Mass Assignment).
     */
    protected $fillable = [
        'resource_id',
        'bucket_name',
        'ministack_bucket_id',
        'used_storage_mb',
    ];

    /**
     * Relasi balik ke tabel resources (Jatah Fasilitas)
     * Setiap brankas (Bucket) dialokasikan di dalam satu fasilitas fisik yang sah.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    /**
     * Relasi ke tabel objects (Daftar File)
     * Satu brankas (Bucket) berfungsi untuk menyimpan banyak file biner (CloudObject).
     */
    public function objects(): HasMany
    {
        return $this->hasMany(CloudObject::class, 'bucket_id');
    }
}