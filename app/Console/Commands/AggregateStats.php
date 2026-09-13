<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AggregateStats extends Command
{
    protected $signature = 'stats:aggregate {--date= : Date to aggregate (default: today)}';

    protected $description = 'Aggregate impressions and clicks into daily_stats';

    public function handle(): void
    {
        $date = $this->option('date') ?: now('Asia/Baku')->toDateString();
        if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            $this->error('Invalid date');

            return;
        }
        $dateSql = DB::connection()->getDriverName() === 'sqlite' ? "DATE(created_at, '+4 hours')" : 'DATE(DATE_ADD(created_at, INTERVAL 4 HOUR))';
        $this->info("Aggregating stats for {$date}...");

        // Aggregate impressions grouped by ad, ad_unit, country, device
        $impressions = DB::table('impressions')
            ->select(
                DB::raw($dateSql.' as date'),
                'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id',
                'country', 'device_type',
                DB::raw('COUNT(*) as impressions')
            )
            ->where('created_at', '>=', Carbon::parse($date, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($date, 'Asia/Baku')->addDay()->utc())
            ->groupBy('date', 'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id', 'country', 'device_type')
            ->get();

        // Aggregate clicks
        $clicks = DB::table('clicks')
            ->select(
                DB::raw($dateSql.' as date'),
                'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id',
                'country', 'device_type',
                DB::raw('COUNT(*) as clicks')
            )
            ->where('created_at', '>=', Carbon::parse($date, 'Asia/Baku')->utc())->where('created_at', '<', Carbon::parse($date, 'Asia/Baku')->addDay()->utc())
            ->groupBy('date', 'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id', 'country', 'device_type')
            ->get();

        // Merge impressions and clicks
        $stats = [];
        foreach ($impressions as $row) {
            $key = "{$row->date}:{$row->ad_id}:{$row->ad_unit_id}:{$row->country}:{$row->device_type}";
            $stats[$key] = [
                'date' => $row->date,
                'ad_id' => $row->ad_id,
                'campaign_id' => $row->campaign_id,
                'ad_unit_id' => $row->ad_unit_id,
                'publisher_id' => $row->publisher_id,
                'advertiser_id' => $row->advertiser_id,
                'country' => $row->country ?? 'ZZ',
                'device_type' => $row->device_type ?? 'unknown',
                'impressions' => $row->impressions,
                'clicks' => 0,
            ];
        }

        foreach ($clicks as $row) {
            $key = "{$row->date}:{$row->ad_id}:{$row->ad_unit_id}:{$row->country}:{$row->device_type}";
            if (isset($stats[$key])) {
                $stats[$key]['clicks'] = $row->clicks;
            } else {
                $stats[$key] = [
                    'date' => $row->date,
                    'ad_id' => $row->ad_id,
                    'campaign_id' => $row->campaign_id,
                    'ad_unit_id' => $row->ad_unit_id,
                    'publisher_id' => $row->publisher_id,
                    'advertiser_id' => $row->advertiser_id,
                    'country' => $row->country ?? 'ZZ',
                    'device_type' => $row->device_type ?? 'unknown',
                    'impressions' => 0,
                    'clicks' => $row->clicks,
                ];
            }
        }

        // Replace one day atomically, including legacy rows with null dimensions.
        $count = 0;
        DB::transaction(function () use ($date, $stats, &$count) {
            DB::table('daily_stats')->whereDate('date', $date)->delete();
            foreach ($stats as $stat) {
                $ctr = $stat['impressions'] > 0
                    ? round(($stat['clicks'] / $stat['impressions']) * 100, 4)
                    : 0;

                DB::table('daily_stats')->updateOrInsert(
                    [
                        'date' => $stat['date'],
                        'ad_id' => $stat['ad_id'],
                        'ad_unit_id' => $stat['ad_unit_id'],
                        'country' => $stat['country'],
                        'device_type' => $stat['device_type'],
                    ],
                    [
                        'campaign_id' => $stat['campaign_id'],
                        'publisher_id' => $stat['publisher_id'],
                        'advertiser_id' => $stat['advertiser_id'],
                        'impressions' => $stat['impressions'],
                        'clicks' => $stat['clicks'],
                        'ctr' => $ctr,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $count++;
            }

        });

        Cache::put('stats:last_aggregate', now()->toIso8601String(), 10800);
        $this->info("Aggregated {$count} stat rows for {$date}.");
    }
}
