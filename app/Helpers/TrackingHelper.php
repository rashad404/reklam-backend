<?php

namespace App\Helpers;

class TrackingHelper
{
    /**
     * Parse user agent into device_type, browser, os
     */
    public static function parseUserAgent(string $ua): array
    {
        $ua = strtolower($ua);

        // Device type
        $deviceType = 'desktop';
        if (preg_match('/mobile|android.*mobile|iphone|ipod|blackberry|iemobile|opera mini|opera mobi/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad|android(?!.*mobile)|kindle|silk/i', $ua)) {
            $deviceType = 'tablet';
        }

        // Browser
        $browser = 'other';
        if (str_contains($ua, 'edg/') || str_contains($ua, 'edge/')) {
            $browser = 'edge';
        } elseif (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) {
            $browser = 'opera';
        } elseif (str_contains($ua, 'chrome') || str_contains($ua, 'crios')) {
            $browser = 'chrome';
        } elseif (str_contains($ua, 'firefox') || str_contains($ua, 'fxios')) {
            $browser = 'firefox';
        } elseif (str_contains($ua, 'safari') && !str_contains($ua, 'chrome')) {
            $browser = 'safari';
        } elseif (str_contains($ua, 'msie') || str_contains($ua, 'trident')) {
            $browser = 'ie';
        } elseif (str_contains($ua, 'samsung')) {
            $browser = 'samsung';
        } elseif (str_contains($ua, 'ucbrowser')) {
            $browser = 'uc';
        }

        // OS
        $os = 'other';
        if (str_contains($ua, 'windows')) {
            $os = 'windows';
        } elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) {
            $os = 'macos';
        } elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod')) {
            $os = 'ios';
        } elseif (str_contains($ua, 'android')) {
            $os = 'android';
        } elseif (str_contains($ua, 'linux')) {
            $os = 'linux';
        } elseif (str_contains($ua, 'cros')) {
            $os = 'chromeos';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os,
        ];
    }

    /**
     * Get country from IP using free ip-api.com (non-commercial use)
     * For production, use MaxMind GeoLite2 local database
     */
    public static function getCountryFromIp(string $ip): ?string
    {
        // Skip private/local IPs
        if (self::isPrivateIp($ip)) {
            return null;
        }

        // Use cache to avoid hitting API for same IP
        $cacheKey = "geo:{$ip}";
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=countryCode");

            if ($response->successful()) {
                $country = $response->json('countryCode');
                \Illuminate\Support\Facades\Cache::put($cacheKey, $country ?? '', 86400); // cache 24h
                return $country;
            }
        } catch (\Exception $e) {
            // Silently fail - geo is optional
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, '', 3600); // cache failure for 1h
        return null;
    }

    private static function isPrivateIp(string $ip): bool
    {
        return str_starts_with($ip, '10.') ||
               str_starts_with($ip, '172.') ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '127.') ||
               str_starts_with($ip, '100.') ||
               $ip === '0.0.0.0' ||
               $ip === '::1';
    }
}
