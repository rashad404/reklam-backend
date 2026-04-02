<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdUnit;
use App\Models\Click;
use App\Models\Impression;
use Illuminate\Http\Request;

class ServeController extends Controller
{
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

        // Find a matching active ad
        $ad = Ad::where('status', 'approved')
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
            ->inRandomOrder()
            ->first();

        if (!$ad) {
            return response()->json(['ad' => null], 200);
        }

        return response()->json([
            'ad' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'image_url' => $ad->image_url,
                'destination_url' => $ad->destination_url,
                'format' => $ad->ad_format,
                'click_url' => url("/api/track/click/{$ad->id}"),
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

        $ad = Ad::with('campaign')->find($request->ad_id);
        $adUnit = AdUnit::find($request->unit_id);

        if (!$ad || !$adUnit) {
            return response()->json(['status' => 'error'], 400);
        }

        Impression::create([
            'ad_id' => $ad->id,
            'ad_unit_id' => $adUnit->id,
            'campaign_id' => $ad->campaign_id,
            'advertiser_id' => $ad->campaign->advertiser_id,
            'publisher_id' => $adUnit->publisher_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Charge CPM if applicable
        if ($ad->campaign->cpm_bid) {
            $cost = $ad->campaign->cpm_bid / 1000;
            $ad->campaign->increment('spent', $cost);
            $ad->campaign->advertiser->decrement('balance', $cost);
        }

        return response()->json(['status' => 'ok']);
    }

    public function trackClick(Request $request, $adId)
    {
        $ad = Ad::with('campaign.advertiser')->find($adId);

        if (!$ad) {
            return redirect('/');
        }

        // Try to find the ad unit from referrer or query
        $unitId = $request->query('unit');
        $adUnit = $unitId ? AdUnit::find($unitId) : null;

        Click::create([
            'ad_id' => $ad->id,
            'ad_unit_id' => $adUnit?->id ?? 0,
            'campaign_id' => $ad->campaign_id,
            'advertiser_id' => $ad->campaign->advertiser_id,
            'publisher_id' => $adUnit?->publisher_id ?? 0,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('Referer'),
        ]);

        // Charge CPC
        if ($ad->campaign->cpc_bid) {
            $cost = $ad->campaign->cpc_bid;
            $ad->campaign->increment('spent', $cost);
            $ad->campaign->advertiser->decrement('balance', $cost);

            // Publisher earning (70%)
            $commission = config('app.platform_commission', 0.30);
            $earning = $cost * (1 - $commission);
            // TODO: Credit publisher balance
        }

        return redirect($ad->destination_url);
    }
}
