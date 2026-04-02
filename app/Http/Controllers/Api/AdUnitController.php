<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdUnit;
use Illuminate\Http\Request;

class AdUnitController extends Controller
{
    public function index(Request $request)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $adUnits = $publisher->adUnits()
            ->withCount(['impressions', 'clicks'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $adUnits,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ad_format' => 'required|in:banner_728x90,banner_300x250,banner_320x50,native,text',
            'website_url' => 'required|url|max:255',
            'page_url' => 'nullable|url|max:255',
        ]);

        $publisher = $request->user()->publisher;

        if (!$publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $adUnit = $publisher->adUnits()->create($request->only([
            'name', 'ad_format', 'website_url', 'page_url',
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $adUnit,
        ], 201);
    }

    public function update(Request $request, AdUnit $adUnit)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher || $adUnit->publisher_id !== $publisher->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,paused',
            'page_url' => 'nullable|url|max:255',
        ]);

        $adUnit->update($request->only(['name', 'status', 'page_url']));

        return response()->json([
            'status' => 'success',
            'data' => $adUnit->fresh(),
        ]);
    }

    public function destroy(Request $request, AdUnit $adUnit)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher || $adUnit->publisher_id !== $publisher->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $adUnit->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ad unit deleted',
        ]);
    }

    public function code(Request $request, AdUnit $adUnit)
    {
        $publisher = $request->user()->publisher;

        if (!$publisher || $adUnit->publisher_id !== $publisher->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $format = str_replace('banner_', '', $adUnit->ad_format);
        $code = '<div id="reklam-ad" data-unit="' . $adUnit->id . '" data-format="' . $format . '"></div>' . "\n"
            . '<script src="https://reklam.biz/serve.js"></script>';

        return response()->json([
            'status' => 'success',
            'data' => [
                'embed_code' => $code,
                'ad_unit' => $adUnit,
            ],
        ]);
    }
}
