<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\Click;
use App\Models\Impression;
use App\Models\Publisher;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_publishers' => Publisher::count(),
                'total_advertisers' => Advertiser::count(),
                'total_impressions' => Impression::count(),
                'total_clicks' => Click::count(),
                'pending_publishers' => Publisher::where('status', 'pending')->count(),
                'pending_ads' => Ad::where('status', 'pending')->count(),
            ],
        ]);
    }

    public function publishers(Request $request)
    {
        $status = $request->query('status', 'pending');

        $publishers = Publisher::where('status', $status)
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $publishers,
        ]);
    }

    public function approvePublisher(Request $request, Publisher $publisher)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $publisher->update([
            'status' => $request->status,
            'approved_at' => $request->status === 'approved' ? now() : null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $publisher->fresh(),
        ]);
    }

    public function ads(Request $request)
    {
        $status = $request->query('status', 'pending');

        $ads = Ad::where('status', $status)
            ->with(['campaign.advertiser.user:id,name'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $ads,
        ]);
    }

    public function approveAd(Request $request, Ad $ad)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $ad->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'data' => $ad->fresh(),
        ]);
    }
}
