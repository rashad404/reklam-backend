<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function dashboard(Request $request)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher) {
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
            'website_url' => 'required|url|max:255',
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
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $publisher,
        ], 201);
    }

    public function earnings(Request $request)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher) {
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

        $publisher = $request->user()->publisher;

        if (!$publisher) {
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
