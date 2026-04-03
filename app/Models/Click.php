<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Click extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'impression_id', 'ad_id', 'ad_unit_id', 'campaign_id',
        'advertiser_id', 'publisher_id', 'ip', 'user_agent',
        'referrer', 'country', 'device_type', 'browser', 'os',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function adUnit()
    {
        return $this->belongsTo(AdUnit::class);
    }

    public function impression()
    {
        return $this->belongsTo(Impression::class);
    }
}
