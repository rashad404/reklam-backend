<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Impression extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ad_id', 'ad_unit_id', 'campaign_id', 'advertiser_id',
        'publisher_id', 'ip', 'user_agent', 'country',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function adUnit()
    {
        return $this->belongsTo(AdUnit::class);
    }
}
