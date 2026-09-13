<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdUnit;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Impression;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    private function range(Request $request): array
    {
        $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from = $request->query('from', now('Asia/Baku')->subDays(29)->toDateString());
        $to = $request->query('to', now('Asia/Baku')->toDateString());
        abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to), false) > 366 || $from > $to, 422, 'Choose a date range of at most 366 days.');

        return [$from, $to];
    }

    private function dateExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite' ? "DATE(created_at, '+4 hours')" : 'DATE(DATE_ADD(created_at, INTERVAL 4 HOUR))';
    }

    /**
     * Campaign stats overview with time range
     */
    public function campaignStats(Request $request, $campaignId)
    {
        $advertiser = $request->user()->advertiser;
        if (! $advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        $campaign = $advertiser->campaigns()->find($campaignId);
        if (! $campaign) {
            return response()->json(['status' => 'error', 'message' => 'Campaign not found'], 404);
        }

        [$from, $to] = $this->range($request);

        // Daily breakdown
        $daily = $this->getDailyStats('campaign_id', $campaignId, $from, $to);

        // By device
        $byDevice = $this->getBreakdown('device_type', 'campaign_id', $campaignId, $from, $to);

        // By country
        $byCountry = $this->getBreakdown('country', 'campaign_id', $campaignId, $from, $to);

        // By browser
        $byBrowser = Impression::where('campaign_id', $campaignId)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())
            ->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $totals = $this->getTotals('campaign_id', $campaignId, $from, $to);
        // Financial fields are deliberately excluded from date-filtered traffic reports.

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => $totals,
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
        if (! $publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        $adUnit = $publisher->adUnits()->find($adUnitId);
        if (! $adUnit) {
            return response()->json(['status' => 'error', 'message' => 'Ad unit not found'], 404);
        }

        [$from, $to] = $this->range($request);

        $daily = $this->getDailyStats('ad_unit_id', $adUnitId, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'ad_unit_id', $adUnitId, $from, $to);
        $byCountry = $this->getBreakdown('country', 'ad_unit_id', $adUnitId, $from, $to);

        $totals = $this->getTotals('ad_unit_id', $adUnitId, $from, $to);

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => $totals,
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
        if (! $advertiser) {
            return response()->json(['status' => 'error', 'message' => 'Not an advertiser'], 403);
        }

        [$from, $to] = $this->range($request);

        $daily = $this->getDailyStats('advertiser_id', $advertiser->id, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'advertiser_id', $advertiser->id, $from, $to);
        $byCountry = $this->getBreakdown('country', 'advertiser_id', $advertiser->id, $from, $to);

        // Per campaign breakdown
        $byCampaign = Impression::where('advertiser_id', $advertiser->id)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->select('campaign_id', DB::raw('COUNT(*) as impressions'))
            ->groupBy('campaign_id')
            ->get()
            ->map(function ($row) use ($from, $to) {
                $clicks = Click::where('campaign_id', $row->campaign_id)
                    ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();
                $campaign = Campaign::find($row->campaign_id);

                return [
                    'campaign_id' => $row->campaign_id,
                    'name' => $campaign?->name,
                    'impressions' => $row->impressions,
                    'clicks' => $clicks,
                    'ctr' => $row->impressions > 0 ? round(($clicks / $row->impressions) * 100, 2) : 0,
                ];
            });

        $totals = $this->getTotals('advertiser_id', $advertiser->id, $from, $to);

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => $totals,
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
        if (! $publisher) {
            return response()->json(['status' => 'error', 'message' => 'Not a publisher'], 403);
        }

        [$from, $to] = $this->range($request);

        $daily = $this->getDailyStats('publisher_id', $publisher->id, $from, $to);
        $byDevice = $this->getBreakdown('device_type', 'publisher_id', $publisher->id, $from, $to);
        $byCountry = $this->getBreakdown('country', 'publisher_id', $publisher->id, $from, $to);

        // Per ad unit breakdown
        $byAdUnit = Impression::where('publisher_id', $publisher->id)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->select('ad_unit_id', DB::raw('COUNT(*) as impressions'))
            ->groupBy('ad_unit_id')
            ->get()
            ->map(function ($row) use ($from, $to) {
                $clicks = Click::where('ad_unit_id', $row->ad_unit_id)
                    ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();
                $adUnit = AdUnit::find($row->ad_unit_id);

                return [
                    'ad_unit_id' => $row->ad_unit_id,
                    'name' => $adUnit?->name,
                    'impressions' => $row->impressions,
                    'clicks' => $clicks,
                    'ctr' => $row->impressions > 0 ? round(($clicks / $row->impressions) * 100, 2) : 0,
                ];
            });

        $totals = $this->getTotals('publisher_id', $publisher->id, $from, $to);

        return response()->json([
            'status' => 'success',
            'data' => [
                'totals' => $totals,
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
     * Get total and unique counts for impressions and clicks
     */
    private function getTotals(string $column, $value, string $from, string $to): array
    {
        $impressions = Impression::where($column, $value)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();
        $uniqueImpressions = Impression::where($column, $value)
            ->where('is_unique', true)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();
        $clicks = Click::where($column, $value)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();
        $uniqueClicks = Click::where($column, $value)
            ->where('is_unique', true)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())->count();

        return [
            'impressions' => $impressions,
            'unique_impressions' => $uniqueImpressions,
            'clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0,
        ];
    }

    /**
     * Get daily impressions and clicks for a given entity
     */
    private function getDailyStats(string $column, $value, string $from, string $to): array
    {
        $impressions = Impression::where($column, $value)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->select(DB::raw($this->dateExpression().' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        $clicks = Click::where($column, $value)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->select(DB::raw($this->dateExpression().' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->orderBy('date')
            ->pluck('count', 'date')->toArray();

        // Build complete date range
        $result = [];
        $current = Carbon::parse($from);
        $end = Carbon::parse($to);
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
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
            ->whereNotNull($dimension)
            ->select($dimension, DB::raw('COUNT(*) as count'))
            ->groupBy($dimension)->orderByDesc('count')
            ->limit(20)
            ->pluck('count', $dimension)->toArray();

        $clicks = Click::where($filterColumn, $filterValue)
            ->where('created_at', '>=', Carbon::parse($from, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($to, 'Asia/Baku')->addDay()->utc())
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

        usort($result, fn ($a, $b) => $b['impressions'] - $a['impressions']);

        return $result;
    }
}
