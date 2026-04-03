<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'wallet_id',
        'wallet_access_token',
        'wallet_refresh_token',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'wallet_access_token',
        'wallet_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function advertiser()
    {
        return $this->hasOne(Advertiser::class);
    }

    public function publisher()
    {
        return $this->hasOne(Publisher::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
