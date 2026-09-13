<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Advertiser;
use App\Models\Click;
use App\Models\Impression;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json(['data' => [
            'total_publishers' => Publisher::count(), 'total_advertisers' => Advertiser::count(),
            'total_impressions' => Impression::count(), 'total_clicks' => Click::count(),
            'pending_publishers' => Publisher::where('status', 'pending')->count(), 'pending_ads' => Ad::where('status', 'pending')->count(),
        ]]);
    }

    public function publishers(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,approved,rejected,suspended']);

        return response()->json(['data' => Publisher::where('status', $request->query('status', 'pending'))->with('user:id,name,email')->latest()->paginate(20)]);
    }

    public function ads(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,approved,rejected']);

        return response()->json(['data' => Ad::where('status', $request->query('status', 'pending'))->with('campaign.advertiser.user:id,name,email')->latest()->paginate(20)]);
    }

    public function approvePublisher(Request $request, Publisher $publisher)
    {
        return $this->decide($request, $publisher, 'publisher');
    }

    public function approveAd(Request $request, Ad $ad)
    {
        return $this->decide($request, $ad, 'ad');
    }

    private function decide(Request $request, $subject, string $type)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected'.($type === 'publisher' ? ',suspended' : ''), 'reason' => 'nullable|string|max:1000']);
        if ($data['status'] !== 'approved') {
            $request->validate(['reason' => 'required|string|min:3|max:1000']);
        }
        if ($type === 'publisher' && $data['status'] === 'approved') {
            abort_unless($subject->verified_at, 422, 'Verify site ownership before approval.');
        }
        DB::transaction(function () use ($request, $subject, $data, $type) {
            $subject->status = $data['status'];
            $subject->review_reason = $data['reason'] ?? null;
            if ($type === 'publisher') {
                $subject->approved_at = $data['status'] === 'approved' ? now() : null;
            }
            $subject->save();
            DB::table('moderation_decisions')->insert(['actor_id' => $request->user()->id, 'subject_type' => $type, 'subject_id' => $subject->id, 'status' => $data['status'], 'reason' => $data['reason'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        });

        return response()->json(['data' => $subject->fresh()]);
    }
}
