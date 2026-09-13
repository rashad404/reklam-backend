<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'publisher_id', 'name', 'ad_format', 'website_url', 'page_url', 'status',
    ];

    public function publisher()
    {
        return $this->belongsTo(Publisher::class);
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
