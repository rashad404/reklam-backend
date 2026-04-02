<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Campaign;
use Illuminate\Http\Request;

class AdController extends Controller
{
    public function index(Request $request)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $ads = Ad::whereHas('campaign', function ($q) use ($advertiser) {
            $q->where('advertiser_id', $advertiser->id);
        })->with('campaign:id,name')->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $ads,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url',
            'destination_url' => 'required|url',
            'ad_format' => 'required|in:banner_728x90,banner_300x250,banner_320x50,native,text',
        ]);

        $advertiser = $request->user()->advertiser;
        $campaign = Campaign::find($request->campaign_id);

        if (!$advertiser || $campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $ad = $campaign->ads()->create($request->only([
            'title', 'description', 'image_url', 'destination_url', 'ad_format',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $ad,
        ], 201);
    }

    public function update(Request $request, Ad $ad)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $ad->campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image_url' => 'nullable|url',
            'destination_url' => 'sometimes|url',
        ]);

        $ad->update($request->only(['title', 'description', 'image_url', 'destination_url']));
        $ad->update(['status' => 'pending']); // Re-review after edit

        return response()->json([
            'status' => 'success',
            'data' => $ad->fresh(),
        ]);
    }

    public function destroy(Request $request, Ad $ad)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $ad->campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $ad->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ad deleted',
        ]);
    }
}
