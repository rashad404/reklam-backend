<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    protected $fillable = [
        'campaign_id', 'title', 'description', 'image_url',
        'destination_url', 'ad_format', 'status',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function impressions()
    {
        return $this->hasMany(Impression::class);
    }

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }
}
