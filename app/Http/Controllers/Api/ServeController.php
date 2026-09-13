<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TrackingHelper;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdUnit;
use App\Models\Click;
use App\Models\Impression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServeController extends Controller
{
    // Time windows
    private const IMPRESSION_WINDOW = 1800;   // 30 min - same visitor sees same ad on same unit

    private const CLICK_WINDOW = 7200;        // 2 hours - same visitor clicks same ad

    private const IP_CLICK_WINDOW = 3600;     // 1 hour - same IP+UA clicks same ad (backup dedup)

    private const CLICK_RATE_WINDOW = 60;     // 1 min

    private const CLICK_RATE_LIMIT = 5;       // max 5 clicks per min per IP

    private const IMPRESSION_RATE_WINDOW = 60;

    private const IMPRESSION_RATE_LIMIT = 30;

    // Signed visitor ID: alphabet used in generation (no 8, no E, no e)
    private const SAFE_CHARS = 'abcdfghijlmnopqrstuvwxyz012345679';

    private const BOT_PATTERNS = [
        'bot', 'crawler', 'spider', 'slurp', 'mediapartners',
        'curl', 'wget', 'python', 'java/', 'php/', 'go-http',
        'headless', 'phantom', 'selenium', 'puppeteer', 'playwright',
        'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom',
    ];

    public function serve(Request $request)
    {
        $request->validate(['unit' => 'required|integer']);
        if (! config('reklam.delivery_enabled')) {
            return response()->json(['ad' => null]);
        }
        $adUnit = AdUnit::with('publisher')->find($request->unit);
        if (! $adUnit || $adUnit->status !== 'active' || $adUnit->publisher?->status !== 'approved' || ! $adUnit->publisher->verified_at) {
            return response()->json(['ad' => null]);
        }
        $referrer = $request->header('Referer', '');
        if (! $this->isReferrerValid($referrer, $adUnit->website_url)) {
            return response()->json(['ad' => null]);
        }
        if ($referrer) {
            $adUnit->last_seen_at = now();
            $adUnit->save();
        }
        $today = now(config('reklam.timezone'))->toDateString();
        $ads = Ad::where('status', 'approved')->where('ad_format', $adUnit->ad_format)
            ->whereHas('campaign', function ($q) use ($today) {
                $q->where('status', 'active')->whereColumn('spent', '<', 'budget')
                    ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today))
                    ->whereHas('advertiser', fn ($q) => $q->where('status', 'active')->where('balance', '>', 0));
            })->with('campaign.advertiser')->get()->filter(fn ($ad) => $this->eligible($ad, $adUnit))->values();
        if ($ads->isEmpty()) {
            return response()->json(['ad' => null]);
        }
        // Choose a pricing pool first. Raw CPC and CPM bids are never compared.
        $groups = $ads->groupBy(fn ($ad) => $ad->campaign->cpc_bid ? 'cpc' : 'cpm')->values();
        $ad = $this->selectByWeight($groups->random());
        $payload = ['id' => (string) Str::uuid(), 'ad' => $ad->id, 'unit' => $adUnit->id, 'exp' => time() + 900];
        $token = Crypt::encryptString(json_encode($payload));

        return response()->json(['ad' => [
            'id' => $ad->id, 'title' => $ad->title, 'description' => $ad->description, 'image_url' => $ad->image_url,
            'format' => $ad->ad_format, 'click_url' => url('/api/track/click/'.$ad->id).'?token='.urlencode($token),
        ], 'unit_id' => $adUnit->id, 'token' => $token])->header('Cache-Control', 'no-store');
    }

    private function eligible($ad, $unit): bool
    {
        if (! config('reklam.delivery_enabled') || ! $ad || ! $unit || $ad->status !== 'approved' || $unit->status !== 'active') {
            return false;
        }
        $c = $ad->campaign;
        if ($c && ($c->daily_budget || ! empty($c->targeting_json))) {
            return false;
        }
        $today = now(config('reklam.timezone'))->toDateString();
        if (! $c || $c->status !== 'active' || $c->spent >= $c->budget || $c->advertiser?->status !== 'active' || $c->advertiser->balance <= 0) {
            return false;
        }
        if ($c->start_date && $c->start_date->toDateString() > $today || $c->end_date && $c->end_date->toDateString() < $today) {
            return false;
        }

        return $unit->publisher?->status === 'approved' && (bool) $unit->publisher->verified_at && $ad->ad_format === $unit->ad_format;
    }

    private function delivery(Request $request): ?array
    {
        try {
            $p = json_decode(Crypt::decryptString($request->input('token', '')), true);
            if (! is_array($p) || ($p['exp'] ?? 0) < time() || ! isset($p['id'],$p['ad'],$p['unit'])) {
                return null;
            }

            return $p;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function claim(array $delivery, string $kind): bool
    {
        return DB::table('delivery_events')->insertOrIgnore([
            'delivery_id' => $delivery['id'], 'kind' => $kind, 'created_at' => now(),
        ]) === 1;
    }

    public function trackViewable(Request $request)
    {
        $p = $this->delivery($request);
        if ($p && $this->eligible(Ad::with('campaign.advertiser')->find($p['ad']), AdUnit::with('publisher')->find($p['unit']))
            && DB::table('delivery_events')->where('delivery_id', $p['id'])->where('kind', 'impression')->exists()) {
            $this->claim($p, 'viewable');
        }

        return response()->json(['status' => 'ok']);
    }

    public function trackImpression(Request $request)
    {
        $delivery = $this->delivery($request);
        if (! $delivery) {
            return response()->json(['status' => 'ok']);
        }
        $request->merge(['ad_id' => $delivery['ad'], 'unit_id' => $delivery['unit']]);

        $ip = $this->getClientIp($request);
        $ua = $request->userAgent() ?? '';
        $vid = hash('sha256', $ip.'|'.$ua);
        $sid = $request->input('sid', '');

        // 1. Bot check
        if ($this->isBot($ua)) {
            return response()->json(['status' => 'ok']);
        }

        // 2. Validate visitor ID signature
        if (! $delivery) {
            Log::info('Impression rejected: invalid vid', ['vid' => $vid, 'ip' => $ip]);

            return response()->json(['status' => 'ok']);
        }

        // 3. Rate limit per IP
        $rateKey = "imp_rate:{$ip}";
        Cache::add($rateKey, 0, 60);
        $rateCount = Cache::increment($rateKey);
        if ($rateCount >= self::IMPRESSION_RATE_LIMIT) {
            return response()->json(['status' => 'ok']);
        }

        $ad = Ad::with('campaign.advertiser')->find($request->ad_id);
        $adUnit = AdUnit::find($request->unit_id);

        if (! $this->eligible($ad, $adUnit)) {
            return response()->json(['status' => 'error'], 400);
        }

        // 4. Referrer check
        $referrer = $request->header('Referer', '');
        if (! $this->isReferrerValid($referrer, $adUnit->website_url)) {
            Log::info('Impression referrer mismatch', ['referrer' => $referrer, 'unit' => $adUnit->website_url, 'ip' => $ip]);

            return response()->json(['status' => 'ok']);
        }

        if (! $this->claim($delivery, 'impression')) {
            return response()->json(['status' => 'ok']);
        }

        // 5. Check uniqueness (dedup)
        $isUnique = true;

        $dedupKey = "imp:{$vid}:{$request->ad_id}:{$request->unit_id}";
        $isUnique = Cache::add($dedupKey, true, self::IMPRESSION_WINDOW);

        // 6. Always record impression
        $parsed = TrackingHelper::parseUserAgent($ua);
        $country = TrackingHelper::getCountryFromIp($ip);

        Impression::create([
            'ad_id' => $ad->id,
            'ad_unit_id' => $adUnit->id,
            'campaign_id' => $ad->campaign_id,
            'advertiser_id' => $ad->campaign->advertiser_id,
            'publisher_id' => $adUnit->publisher_id,
            'ip' => $ip,
            'user_agent' => substr($ua, 0, 255),
            'country' => $country,
            'device_type' => $parsed['device_type'],
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'is_unique' => $isUnique,
        ]);

        // 7. Only charge CPM for unique impressions
        if ($isUnique && $ad->campaign->cpm_bid && $ad->campaign->advertiser) {
            $cost = $ad->campaign->cpm_bid / 1000;
            if ($ad->campaign->advertiser->balance >= $cost) {
                $ad->campaign->increment('spent', $cost);
                $ad->campaign->advertiser->decrement('balance', $cost);

                $commission = (float) env('PLATFORM_COMMISSION', 0.30);
                $earning = round($cost * (1 - $commission), 4);
                $publisher = $adUnit->publisher;
                if ($publisher) {
                    $publisher->increment('balance', $earning);
                    $publisher->increment('total_earned', $earning);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    public function trackClick(Request $request, $adId)
    {
        $ad = Ad::with('campaign.advertiser')->find($adId);

        if (! $ad) {
            return redirect('/');
        }

        $ip = $this->getClientIp($request);
        $ua = $request->userAgent() ?? '';
        $delivery = $this->delivery($request);
        if (! $delivery || (int) $delivery['ad'] !== (int) $adId) {
            return redirect($ad->destination_url);
        }
        $vid = hash('sha256', $ip.'|'.$ua);
        $unitId = $delivery['unit'];
        $adUnit = $unitId ? AdUnit::find($unitId) : null;

        // 1. Bot check
        if ($this->isBot($ua)) {
            return redirect($ad->destination_url);
        }

        // 2. Validate visitor ID signature
        if (! $this->eligible($ad, $adUnit)) {
            Log::info('Click rejected: invalid vid', ['vid' => $vid, 'ip' => $ip, 'ad' => $adId]);

            return redirect($ad->destination_url);
        }

        // 3. Rate limit per IP
        $rateKey = "click_rate:{$ip}";
        Cache::add($rateKey, 0, 60);
        $rateCount = Cache::increment($rateKey);
        if ($rateCount >= self::CLICK_RATE_LIMIT) {
            return redirect($ad->destination_url);
        }

        // 4. Referrer check
        if ($adUnit) {
            $referrer = $request->header('Referer', '');
            if (! $this->isReferrerValid($referrer, $adUnit->website_url)) {
                Log::info('Click referrer mismatch', ['referrer' => $referrer, 'unit' => $adUnit->website_url, 'ip' => $ip]);

                return redirect($ad->destination_url);
            }
        }

        // 5. Check uniqueness
        $isUnique = true;

        if (! $this->claim($delivery, 'click')) {
            return redirect($ad->destination_url);
        }
        $dedupKey = "click:{$vid}:{$adId}";
        $isUnique = Cache::add($dedupKey, true, self::CLICK_WINDOW);

        // 6. Always record click
        $parsed = TrackingHelper::parseUserAgent($ua);
        $country = TrackingHelper::getCountryFromIp($ip);

        Click::create([
            'ad_id' => $ad->id,
            'ad_unit_id' => $adUnit?->id ?? 0,
            'campaign_id' => $ad->campaign_id,
            'advertiser_id' => $ad->campaign->advertiser_id,
            'publisher_id' => $adUnit?->publisher_id ?? 0,
            'ip' => $ip,
            'user_agent' => substr($ua, 0, 255),
            'referrer' => substr($request->header('Referer', ''), 0, 255),
            'country' => $country,
            'device_type' => $parsed['device_type'],
            'browser' => $parsed['browser'],
            'os' => $parsed['os'],
            'is_unique' => $isUnique,
        ]);

        // Only charge CPC for unique clicks
        if ($isUnique && $ad->campaign->cpc_bid && $ad->campaign->advertiser) {
            $cost = $ad->campaign->cpc_bid;
            if ($ad->campaign->advertiser->balance >= $cost) {
                $ad->campaign->increment('spent', $cost);
                $ad->campaign->advertiser->decrement('balance', $cost);

                $commission = (float) env('PLATFORM_COMMISSION', 0.30);
                $earning = round($cost * (1 - $commission), 4);
                $publisher = $adUnit ? $adUnit->publisher : null;
                if ($publisher) {
                    $publisher->increment('balance', $earning);
                    $publisher->increment('total_earned', $earning);
                }
            }
        }

        return redirect($ad->destination_url);
    }

    /**
     * Weighted random: higher bid = shown more, but never 0% for low bidders
     */
    private function selectByWeight($ads)
    {
        if ($ads->count() === 1) {
            return $ads->first();
        }

        $weights = [];
        foreach ($ads as $ad) {
            $bid = max((float) ($ad->campaign->cpc_bid ?? 0), (float) ($ad->campaign->cpm_bid ?? 0));
            // Minimum weight of 1 so every ad has a chance
            $weights[] = max($bid * 100, 1);
        }

        $totalWeight = array_sum($weights);
        $random = mt_rand(1, max(1, (int) ceil($totalWeight)));

        $cumulative = 0;
        foreach ($ads as $i => $ad) {
            $cumulative += $weights[$i];
            if ($random <= $cumulative) {
                return $ad;
            }
        }

        return $ads->last();
    }

    /**
     * Check if referrer matches publisher's website (allows empty referrer)
     */
    private function isReferrerValid(string $referrer, string $unitUrl): bool
    {
        if (empty($referrer)) {
            return true;
        }

        $referrerHost = parse_url($referrer, PHP_URL_HOST) ?? '';
        $unitHost = parse_url($unitUrl, PHP_URL_HOST) ?? '';

        if (empty($unitHost)) {
            return true;
        }

        // Strip www for comparison
        $referrerHost = preg_replace('/^www\./', '', $referrerHost);
        $unitHost = preg_replace('/^www\./', '', $unitHost);

        return strtolower($referrerHost) === strtolower($unitHost) || str_ends_with(strtolower($referrerHost), '.'.strtolower($unitHost));
    }

    /**
     * Get real client IP behind proxies/CDN
     */
    private function getClientIp(Request $request): string
    {
        return $request->ip() ?? '0.0.0.0';
    }

    /**
     * Check if user agent looks like a bot
     */
    private function isBot(string $ua): bool
    {
        if (empty($ua) || strlen($ua) < 20) {
            return true;
        }

        $uaLower = strtolower($ua);
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($uaLower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
