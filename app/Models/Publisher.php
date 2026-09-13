<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    protected $fillable = [
        'user_id', 'website_url', 'website_name', 'category', 'status', 'approved_at',
        'balance', 'total_earned', 'verification_token', 'verified_at', 'review_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'commission_free_started_at' => 'immutable_datetime',
        'commission_free_until' => 'immutable_datetime',
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
    ];

    protected $appends = ['commission_offer'];

    protected static function booted(): void
    {
        static::saving(function (Publisher $publisher) {
            if ($publisher->status === 'approved' && $publisher->isDirty('status') && ! $publisher->commission_free_started_at) {
                $start = now()->toImmutable();
                $publisher->commission_free_started_at = $start;
                $publisher->commission_free_until = $start->addMonthsNoOverflow(6);
            }
        });
    }

    public function platformCommissionRate(): float
    {
        $now = now();
        if ($this->commission_free_started_at && $this->commission_free_until
            && $now->greaterThanOrEqualTo($this->commission_free_started_at)
            && $now->lessThan($this->commission_free_until)) {
            return 0.0;
        }

        return max(0.0, min(1.0, (float) config('reklam.platform_commission', 0.30)));
    }

    public function getCommissionOfferAttribute(): array
    {
        return [
            'months' => 6,
            'starts_at' => $this->commission_free_started_at?->toIso8601String(),
            'ends_at' => $this->commission_free_until?->toIso8601String(),
            'active' => $this->commission_free_started_at && $this->commission_free_until
                && now()->greaterThanOrEqualTo($this->commission_free_started_at) && now()->lessThan($this->commission_free_until),
            'platform_percent' => $this->platformCommissionRate() * 100,
            'standard_platform_percent' => (float) config('reklam.platform_commission', 0.30) * 100,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adUnits()
    {
        return $this->hasMany(AdUnit::class);
    }
}
