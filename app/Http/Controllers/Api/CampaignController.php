<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $campaigns = $advertiser->campaigns()
            ->withCount(['ads', 'impressions', 'clicks'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $campaigns,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:display,native,text',
            'budget' => 'required|numeric|min:1',
            'daily_budget' => 'nullable|numeric|min:0.01',
            'cpc_bid' => 'nullable|numeric|min:0.01',
            'cpm_bid' => 'nullable|numeric|min:0.01',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'targeting_json' => 'nullable|array',
        ]);

        $advertiser = $request->user()->advertiser;

        if (!$advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $campaign = $advertiser->campaigns()->create($request->only([
            'name', 'type', 'budget', 'daily_budget',
            'cpc_bid', 'cpm_bid', 'start_date', 'end_date', 'targeting_json',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $campaign,
        ], 201);
    }

    public function show(Request $request, Campaign $campaign)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $campaign->loadCount(['impressions', 'clicks']);

        return response()->json([
            'status' => 'success',
            'data' => $campaign,
        ]);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'budget' => 'sometimes|numeric|min:1',
            'daily_budget' => 'nullable|numeric|min:0.01',
            'cpc_bid' => 'nullable|numeric|min:0.01',
            'cpm_bid' => 'nullable|numeric|min:0.01',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'targeting_json' => 'nullable|array',
        ]);

        $campaign->update($request->only([
            'name', 'budget', 'daily_budget',
            'cpc_bid', 'cpm_bid', 'start_date', 'end_date', 'targeting_json',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $campaign->fresh(),
        ]);
    }

    public function updateStatus(Request $request, Campaign $campaign)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:active,paused',
        ]);

        $campaign->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'data' => $campaign->fresh(),
        ]);
    }

    public function stats(Request $request, Campaign $campaign)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser || $campaign->advertiser_id !== $advertiser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $stats = $campaign->dailyStats()
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }
}
