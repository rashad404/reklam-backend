<?php

namespace App\Console\Commands;

use App\Models\DailyStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateStats extends Command
{
    protected $signature = 'stats:aggregate {--date= : Date to aggregate (default: today)}';
    protected $description = 'Aggregate impressions and clicks into daily_stats';

    public function handle(): void
    {
        $date = $this->option('date') ?: now()->toDateString();
        $this->info("Aggregating stats for {$date}...");

        // Aggregate impressions grouped by ad, ad_unit, country, device
        $impressions = DB::table('impressions')
            ->select(
                DB::raw("DATE(created_at) as date"),
                'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id',
                'country', 'device_type',
                DB::raw('COUNT(*) as impressions')
            )
            ->whereDate('created_at', $date)
            ->groupBy('date', 'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id', 'country', 'device_type')
            ->get();

        // Aggregate clicks
        $clicks = DB::table('clicks')
            ->select(
                DB::raw("DATE(created_at) as date"),
                'ad_id', 'campaign_id', 'ad_unit_id', 'publisher_id', 'advertiser_id',
                'country', 'device_type',
                DB::raw('COUNT(*) as clicks')
            )
            ->whereDate('created_at', $date)
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
                'country' => $row->country,
                'device_type' => $row->device_type,
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
                    'country' => $row->country,
                    'device_type' => $row->device_type,
                    'impressions' => 0,
                    'clicks' => $row->clicks,
                ];
            }
        }

        // Upsert into daily_stats
        $count = 0;
        foreach ($stats as $stat) {
            $ctr = $stat['impressions'] > 0
                ? round(($stat['clicks'] / $stat['impressions']) * 100, 4)
                : 0;

            DailyStat::updateOrCreate(
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
                ]
            );
            $count++;
        }

        $this->info("Aggregated {$count} stat rows for {$date}.");
    }
}
