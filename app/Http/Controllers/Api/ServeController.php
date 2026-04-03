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
use Illuminate\Support\Facades\Log;

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
        $request->validate([
            'unit' => 'required',
            'format' => 'nullable|string',
        ]);

        $adUnit = AdUnit::where('id', $request->unit)
            ->where('status', 'active')
            ->first();

        if (!$adUnit) {
            return response()->json(['ad' => null], 200);
        }

        $ads = Ad::where('status', 'approved')
            ->where('ad_format', $adUnit->ad_format)
            ->whereHas('campaign', function ($q) {
                $q->where('status', 'active')
                    ->where(function ($q2) {
                        $q2->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($q2) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                    ->whereColumn('spent', '<', 'budget');
            })
            ->with('campaign:id,advertiser_id,cpc_bid,cpm_bid')
            ->get();

        if ($ads->isEmpty()) {
            return response()->json(['ad' => null], 200);
        }

        // Weighted random selection: higher bid = shown more often
        $ad = $this->selectByWeight($ads);

        // Return relative paths - serve.js will prepend the correct base URL
        $imageUrl = $ad->image_url;
        if ($imageUrl && preg_match('#https?://#', $imageUrl)) {
            $path = parse_url($imageUrl, PHP_URL_PATH);
            if ($path) {
                $imageUrl = $path; // /storage/banners/xxx.png
            }
        }

        return response()->json([
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'image_url' => $imageUrl,
                'destination_url' => $ad->destination_url,
                'format' => $ad->ad_format,
                'click_url' => "/api/track/click/{$ad->id}",
            ],
            'unit_id' => $adUnit->id,
        ]);
    }

    public function trackImpression(Request $request)
    {
        $request->validate([
            'ad_id' => 'required|exists:ads,id',
            'unit_id' => 'required|exists:ad_units,id',
        ]);

        $ip = $this->getClientIp($request);
        $ua = $request->userAgent() ?? '';
        $vid = $request->input('vid', '');
        $sid = $request->input('sid', '');

        // 1. Bot check
        if ($this->isBot($ua)) {
            return response()->json(['status' => 'ok']);
        }

        // 2. Validate visitor ID signature
        if (!$this->isValidVid($vid)) {
            Log::info('Impression rejected: invalid vid', ['vid' => $vid, 'ip' => $ip]);
            return response()->json(['status' => 'ok']);
        }

        // 3. Rate limit per IP
        $rateKey = "imp_rate:{$ip}";
        $rateCount = Cache::get($rateKey, 0);
        if ($rateCount >= self::IMPRESSION_RATE_LIMIT) {
            return response()->json(['status' => 'ok']);
        }
        Cache::put($rateKey, $rateCount + 1, self::IMPRESSION_RATE_WINDOW);

        $ad = Ad::with('campaign.advertiser')->find($request->ad_id);
        $adUnit = AdUnit::find($request->unit_id);

        if (!$ad || !$adUnit) {
            return response()->json(['status' => 'error'], 400);
        }

        // 4. Referrer check
        $referrer = $request->header('Referer', '');
        if (!$this->isReferrerValid($referrer, $adUnit->website_url)) {
            Log::info('Impression referrer mismatch', ['referrer' => $referrer, 'unit' => $adUnit->website_url, 'ip' => $ip]);
            return response()->json(['status' => 'ok']);
        }

        // 5. Check uniqueness (dedup)
        $isUnique = true;

        $dedupKey = "imp:{$vid}:{$request->ad_id}:{$request->unit_id}";
        if (Cache::has($dedupKey)) {
            $isUnique = false;
        } else {
            Cache::put($dedupKey, true, self::IMPRESSION_WINDOW);
            // Secondary dedup: IP + UA hash
            $uaHash = substr(md5($ua), 0, 8);
            $ipDedupKey = "imp_ip:{$ip}:{$uaHash}:{$request->ad_id}:{$request->unit_id}";
            if (Cache::has($ipDedupKey)) {
                $isUnique = false;
            } else {
                Cache::put($ipDedupKey, true, self::IMPRESSION_WINDOW);
            }
        }

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

        if (!$ad) {
            return redirect('/');
        }

        $ip = $this->getClientIp($request);
        $ua = $request->userAgent() ?? '';
        $vid = $request->query('vid', '');
        $unitId = $request->query('unit');
        $adUnit = $unitId ? AdUnit::find($unitId) : null;

        // 1. Bot check
        if ($this->isBot($ua)) {
            return redirect($ad->destination_url);
        }

        // 2. Validate visitor ID signature
        if (!$this->isValidVid($vid)) {
            Log::info('Click rejected: invalid vid', ['vid' => $vid, 'ip' => $ip, 'ad' => $adId]);
            return redirect($ad->destination_url);
        }

        // 3. Rate limit per IP
        $rateKey = "click_rate:{$ip}";
        $rateCount = Cache::get($rateKey, 0);
        if ($rateCount >= self::CLICK_RATE_LIMIT) {
            return redirect($ad->destination_url);
        }
        Cache::put($rateKey, $rateCount + 1, self::CLICK_RATE_WINDOW);

        // 4. Referrer check
        if ($adUnit) {
            $referrer = $request->header('Referer', '');
            if (!$this->isReferrerValid($referrer, $adUnit->website_url)) {
                Log::info('Click referrer mismatch', ['referrer' => $referrer, 'unit' => $adUnit->website_url, 'ip' => $ip]);
                return redirect($ad->destination_url);
            }
        }

        // 5. Check uniqueness
        $isUnique = true;

        $dedupKey = "click:{$vid}:{$adId}";
        if (Cache::has($dedupKey)) {
            $isUnique = false;
        } else {
            Cache::put($dedupKey, true, self::CLICK_WINDOW);
            $uaHash = substr(md5($ua), 0, 8);
            $ipDedupKey = "click_ip:{$ip}:{$uaHash}:{$adId}";
            if (Cache::has($ipDedupKey)) {
                $isUnique = false;
            } else {
                Cache::put($ipDedupKey, true, self::IP_CLICK_WINDOW);
            }
        }

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
        $random = mt_rand(1, (int) $totalWeight);

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
     * Validate the signed visitor ID from serve.js
     *
     * Rules:
     * - Exactly 16 characters
     * - Position 1 must be 'k'
     * - Position 7 must be 'z'
     * - Must not contain '8', 'E', or 'e'
     * - Last 2 chars must be valid checksum of positions 3-12
     * - All chars must be from SAFE_CHARS alphabet
     */
    private function isValidVid(string $vid): bool
    {
        if (strlen($vid) !== 16) {
            return false;
        }

        // Signature positions
        if ($vid[1] !== 'k' || $vid[7] !== 'z') {
            return false;
        }

        // Forbidden characters
        if (strpbrk($vid, '8Ee') !== false) {
            return false;
        }

        // All non-signature chars must be in safe alphabet
        for ($i = 0; $i < 16; $i++) {
            if ($i === 1 || $i === 7) continue; // signature positions
            if (strpos(self::SAFE_CHARS, $vid[$i]) === false) {
                return false;
            }
        }

        // Checksum validation: last 2 chars = checksum of positions 3-12
        $middle = substr($vid, 3, 10);
        $expected = $this->checksum($middle);
        $actual = substr($vid, 14, 2);

        if ($expected !== $actual) {
            return false;
        }

        return true;
    }

    /**
     * Same checksum algorithm as serve.js
     */
    private function checksum(string $str): string
    {
        $sum = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $sum = (($sum << 3) - $sum + ord($str[$i])) & 0xffff;
        }
        $chars = self::SAFE_CHARS;
        return $chars[$sum % strlen($chars)] . $chars[($sum >> 5) % strlen($chars)];
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

        return str_contains($referrerHost, $unitHost) || str_contains($unitHost, $referrerHost);
    }

    /**
     * Get real client IP behind proxies/CDN
     */
    private function getClientIp(Request $request): string
    {
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            return trim(explode(',', $forwarded)[0]);
        }

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
