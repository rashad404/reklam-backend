<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use Illuminate\Http\Request;

class AdvertiserController extends Controller
{
    public function dashboard(Request $request)
    {
        $advertiser = $request->user()->advertiser;

        if (!$advertiser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not registered as advertiser',
            ], 403);
        }

        $stats = [
            'balance' => $advertiser->balance,
            'total_spent' => $advertiser->total_spent,
            'impressions' => $advertiser->campaigns()->withCount('impressions')->get()->sum('impressions_count'),
            'clicks' => $advertiser->campaigns()->withCount('clicks')->get()->sum('clicks_count'),
            'active_campaigns' => $advertiser->campaigns()->where('status', 'active')->count(),
        ];

        $stats['ctr'] = $stats['impressions'] > 0
            ? round(($stats['clicks'] / $stats['impressions']) * 100, 2)
            : 0;

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
        ]);

        if ($request->user()->advertiser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Already registered as advertiser',
            ], 422);
        }

        $advertiser = Advertiser::create([
            'user_id' => $request->user()->id,
            'company_name' => $request->company_name,
            'website' => $request->website,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $advertiser,
        ], 201);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // TODO: Integrate with Kimlik.az wallet for actual payment
        // For now, directly add to balance
        $advertiser = $request->user()->advertiser;

        if (!$advertiser) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not registered as advertiser',
            ], 403);
        }

        $advertiser->increment('balance', $request->amount);

        $request->user()->payments()->create([
            'type' => 'deposit',
            'amount' => $request->amount,
            'status' => 'completed',
            'reference' => 'manual_deposit',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => $advertiser->fresh()->balance,
            ],
        ]);
    }
}
