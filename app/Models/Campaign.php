<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'advertiser_id', 'name', 'type', 'budget', 'daily_budget',
        'spent', 'cpc_bid', 'cpm_bid', 'status', 'start_date',
        'end_date', 'targeting_json',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'cpc_bid' => 'decimal:4',
        'cpm_bid' => 'decimal:4',
        'start_date' => 'date',
        'end_date' => 'date',
        'targeting_json' => 'array',
    ];

    public function advertiser()
    {
        return $this->belongsTo(Advertiser::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }

    public function impressions()
    {
        return $this->hasMany(Impression::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }

    public function dailyStats()
    {
        return $this->hasMany(DailyStat::class);
    }
}
