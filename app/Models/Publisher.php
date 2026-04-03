<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    protected $fillable = [
        'user_id', 'website_url', 'website_name', 'category', 'status', 'approved_at',
        'balance', 'total_earned',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adUnits()
    {
        return $this->hasMany(AdUnit::class);
    }
}
