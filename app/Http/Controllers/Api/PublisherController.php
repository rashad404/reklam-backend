<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use App\Services\SiteVerification;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function dashboard(Request $request)
    {
        $publisher = $request->user()->publisher()->first();

        if (! $publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $stats = [
            'balance' => $publisher->balance,
            'total_earned' => $publisher->total_earned,
            'impressions' => $publisher->adUnits()
                ->withCount('impressions')->get()->sum('impressions_count'),
            'clicks' => $publisher->adUnits()
                ->withCount('clicks')->get()->sum('clicks_count'),
            'active_ad_units' => $publisher->adUnits()->where('status', 'active')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'website_url' => 'required|url:http,https|max:255',
            'website_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        if ($request->user()->publisher) {
            return response()->json(['status' => 'error', 'message' => 'Already registered as publisher'], 422);
        }

        $publisher = Publisher::create([
            'user_id' => $request->user()->id,
            'website_url' => $request->website_url,
            'website_name' => $request->website_name,
            'category' => $request->category,
            'verification_token' => bin2hex(random_bytes(24)),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $publisher,
        ], 201);
    }

    public function site(Request $request)
    {
        $publisher = $request->user()->publisher()->first();
        if ($publisher && ! $publisher->verification_token) {
            $publisher->update(['verification_token' => bin2hex(random_bytes(24))]);
        }

        return response()->json(['data' => $publisher]);
    }

    public function verify(Request $request)
    {
        $publisher = $request->user()->publisher()->first();
        abort_unless($publisher, 403);
        $host = strtolower(parse_url($publisher->website_url, PHP_URL_HOST) ?? '');
        abort_unless($host && $publisher->verification_token, 422);
        $records = app(SiteVerification::class)->records('_reklam.'.$host);
        $expected = 'reklam-verification='.$publisher->verification_token;
        abort_unless(in_array($expected, $records, true), 422, 'Verification record was not found. DNS updates can take time.');
        $publisher->update(['verified_at' => now()]);

        return response()->json(['data' => $publisher->fresh()]);
    }

    public function resubmit(Request $request)
    {
        $publisher = $request->user()->publisher()->first();
        abort_unless($publisher && $publisher->status === 'rejected', 422);
        $publisher->update(['status' => 'pending']);

        return response()->json(['data' => $publisher->fresh()]);
    }

    public function earnings(Request $request)
    {
        $publisher = $request->user()->publisher()->first();

        if (! $publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $payments = $request->user()->payments()
            ->whereIn('type', ['earning', 'withdrawal'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $payments,
        ]);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
        ]);

        $publisher = $request->user()->publisher()->first();

        if (! $publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        // TODO: Check available balance and process withdrawal
        $payment = $request->user()->payments()->create([
            'type' => 'withdrawal',
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $payment,
        ]);
    }
}
