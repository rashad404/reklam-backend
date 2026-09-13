<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ad extends Model
{
    use SoftDeletes;

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
