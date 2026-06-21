<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudObject extends Model
{
    protected $table = 'objects'; // Menghubungkan ke tabel 'objects' di DB

    protected $fillable = ['bucket_id', 'nama_file', 'tipe_file', 'ukuran_file'];

    public function bucket(): BelongsTo
    {
        return $this->belongsTo(Bucket::class);
    }
}