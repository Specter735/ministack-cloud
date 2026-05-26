<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi (mass assignment)
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Type casting kolom
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'role'              => 'string',
    ];

    // transaksi user
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // langganan paket user
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    // bucket storage user
    public function buckets()
    {
        return $this->hasMany(Bucket::class);
    }

    // kredensial rahasia user
    public function credential()
    {
        return $this->hasOne(Credential::class);
    }
}