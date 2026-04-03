<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\DailyStat;
use App\Models\Impression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    /**
     * Campaign stats overview with time range
     */
    public function campaignStats(Request $request, $campaignId)
    {
        $advertiser = $request->user()->advertiser;
        if (!$advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $campaign = $advertiser->campaigns()->find($campaignId);
        if (!$campaign) {
            return response()->json(['status' => 'error', 'message' => 'Campaign not found'], 404);
        }

        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        // Daily breakdown
        $daily = $this->getDailyStats('campaign_id', $campaignId, $from, $to);

        // By device
        $byDevice = $this->getBreakdown('device_type', 'campaign_id', $campaignId, $from, $to);

        // By country
        $byCountry = $this->getBreakdown('country', 'campaign_id', $campaignId, $from, $to);

        // By browser
        $byBrowser = Impression::where('campaign_id', $campaignId)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Totals
        $totalImpressions = Impression::where('campaign_id', $campaignId)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
        $totalClicks = Click::where('campaign_id', $campaignId)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => [
                    'impressions' => $totalImpressions,
                    'clicks' => $totalClicks,
                    'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                    'spent' => $campaign->spent,
                    'budget' => $campaign->budget,
                ],
                'daily' => $daily,
                'by_device' => $byDevice,
                'by_country' => $byCountry,
                'by_browser' => $byBrowser,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Ad unit stats for publishers
     */
    public function adUnitStats(Request $request, $adUnitId)
    {
        $publisher = $request->user()->publisher;
        if (!$publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $adUnit = $publisher->adUnits()->find($adUnitId);
        if (!$adUnit) {
            return response()->json(['status' => 'error', 'message' => 'Ad unit not found'], 404);
        }

        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $daily = $this->getDailyStats('ad_unit_id', $adUnitId, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'ad_unit_id', $adUnitId, $from, $to);
        $byCountry = $this->getBreakdown('country', 'ad_unit_id', $adUnitId, $from, $to);

        $totalImpressions = Impression::where('ad_unit_id', $adUnitId)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
        $totalClicks = Click::where('ad_unit_id', $adUnitId)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => [
                    'impressions' => $totalImpressions,
                    'clicks' => $totalClicks,
                    'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                ],
                'daily' => $daily,
                'by_device' => $byDevice,
                'by_country' => $byCountry,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Overall advertiser stats
     */
    public function advertiserOverview(Request $request)
    {
        $advertiser = $request->user()->advertiser;
        if (!$advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $daily = $this->getDailyStats('advertiser_id', $advertiser->id, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'advertiser_id', $advertiser->id, $from, $to);
        $byCountry = $this->getBreakdown('country', 'advertiser_id', $advertiser->id, $from, $to);

        // Per campaign breakdown
        $byCampaign = Impression::where('advertiser_id', $advertiser->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->select('campaign_id', DB::raw('COUNT(*) as impressions'))
            ->groupBy('campaign_id')
            ->get()
            ->map(function ($row) use ($from, $to) {
                $clicks = Click::where('campaign_id', $row->campaign_id)
                    ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
                $campaign = \App\Models\Campaign::find($row->campaign_id);
                return [
                    'campaign_id' => $row->campaign_id,
                    'name' => $campaign?->name,
                    'impressions' => $row->impressions,
                    'clicks' => $clicks,
                    'ctr' => $row->impressions > 0 ? round(($clicks / $row->impressions) * 100, 2) : 0,
                ];
            });

        $totalImpressions = Impression::where('advertiser_id', $advertiser->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
        $totalClicks = Click::where('advertiser_id', $advertiser->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => [
                    'impressions' => $totalImpressions,
                    'clicks' => $totalClicks,
                    'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                    'balance' => $advertiser->balance,
                    'total_spent' => $advertiser->total_spent,
                ],
                'daily' => $daily,
                'by_device' => $byDevice,
                'by_country' => $byCountry,
                'by_campaign' => $byCampaign,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Overall publisher stats
     */
    public function publisherOverview(Request $request)
    {
        $publisher = $request->user()->publisher;
        if (!$publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());

        $daily = $this->getDailyStats('publisher_id', $publisher->id, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'publisher_id', $publisher->id, $from, $to);
        $byCountry = $this->getBreakdown('country', 'publisher_id', $publisher->id, $from, $to);

        // Per ad unit breakdown
        $byAdUnit = Impression::where('publisher_id', $publisher->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->select('ad_unit_id', DB::raw('COUNT(*) as impressions'))
            ->groupBy('ad_unit_id')
            ->get()
            ->map(function ($row) use ($from, $to) {
                $clicks = Click::where('ad_unit_id', $row->ad_unit_id)
                    ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
                $adUnit = \App\Models\AdUnit::find($row->ad_unit_id);
                return [
                    'ad_unit_id' => $row->ad_unit_id,
                    'name' => $adUnit?->name,
                    'impressions' => $row->impressions,
                    'clicks' => $clicks,
                    'ctr' => $row->impressions > 0 ? round(($clicks / $row->impressions) * 100, 2) : 0,
                ];
            });

        $totalImpressions = Impression::where('publisher_id', $publisher->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();
        $totalClicks = Click::where('publisher_id', $publisher->id)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => [
                    'impressions' => $totalImpressions,
                    'clicks' => $totalClicks,
                    'ctr' => $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0,
                ],
                'daily' => $daily,
                'by_device' => $byDevice,
                'by_country' => $byCountry,
                'by_ad_unit' => $byAdUnit,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    /**
     * Get daily impressions and clicks for a given entity
     */
    private function getDailyStats(string $column, $value, string $from, string $to): array
    {
        $impressions = Impression::where($column, $value)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        $clicks = Click::where($column, $value)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        // Build complete date range
        $result = [];
        $current = \Carbon\Carbon::parse($from);
        $end = \Carbon\Carbon::parse($to);
        while ($current <= $end) {
            $d = $current->toDateString();
            $imp = $impressions[$d] ?? 0;
            $clk = $clicks[$d] ?? 0;
            $result[] = [
                'date' => $d,
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $imp > 0 ? round(($clk / $imp) * 100, 2) : 0,
            ];
            $current->addDay();
        }
        return $result;
    }

    /**
     * Get breakdown by a dimension (device_type, country, etc)
     */
    private function getBreakdown(string $dimension, string $filterColumn, $filterValue, string $from, string $to): array
    {
        $impressions = Impression::where($filterColumn, $filterValue)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->whereNotNull($dimension)
            ->select($dimension, DB::raw('COUNT(*) as count'))
            ->groupBy($dimension)->orderByDesc('count')
            ->limit(20)
            ->pluck('count', $dimension)->toArray();

        $clicks = Click::where($filterColumn, $filterValue)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->whereNotNull($dimension)
            ->select($dimension, DB::raw('COUNT(*) as count'))
            ->groupBy($dimension)->orderByDesc('count')
            ->limit(20)
            ->pluck('count', $dimension)->toArray();

        $result = [];
        foreach (array_unique(array_merge(array_keys($impressions), array_keys($clicks))) as $key) {
            $imp = $impressions[$key] ?? 0;
            $clk = $clicks[$key] ?? 0;
            $result[] = [
                'label' => $key,
                'impressions' => $imp,
                'clicks' => $clk,
                'ctr' => $imp > 0 ? round(($clk / $imp) * 100, 2) : 0,
            ];
        }

        usort($result, fn($a, $b) => $b['impressions'] - $a['impressions']);
        return $result;
    }
}
