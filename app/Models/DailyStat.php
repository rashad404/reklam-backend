<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStat extends Model
{
    protected $fillable = [
        'date', 'ad_id', 'campaign_id', 'ad_unit_id',
        'publisher_id', 'advertiser_id', 'impressions',
        'clicks', 'ctr', 'spent', 'earned',
    ];

    protected $casts = [
        'date' => 'date',
        'ctr' => 'decimal:4',
        'spent' => 'decimal:2',
        'earned' => 'decimal:2',
    ];
}
