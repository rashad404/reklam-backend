<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetainTraffic extends Command
{
    protected $signature = 'traffic:retain {--apply : Apply the retention policy}';

    protected $description = 'Anonymize old visitor fields while preserving report counts';

    public function handle(): int
    {
        $cutoff = now()->subDays(max(30, config('reklam.raw_retention_days')));
        foreach (['impressions', 'clicks'] as $table) {
            $query = DB::table($table)->where('created_at', '<', $cutoff)->where(function ($q) use ($table) {
                $q->whereNotNull('ip')->orWhereNotNull('user_agent');
                if ($table === 'clicks') {
                    $q->orWhereNotNull('referrer');
                }
            });
            $this->line($table.': '.$query->count().' visitor records eligible for anonymization');
            if ($this->option('apply')) {
                $query->select('id')->chunkById(5000, function ($rows) use ($table) {
                    DB::table($table)->whereIn('id', $rows->pluck('id'))->update(['ip' => null, 'user_agent' => null] + ($table === 'clicks' ? ['referrer' => null] : []));
                });
            }
        }
        if ($this->option('apply')) {
            DB::table('delivery_events')->where('created_at', '<', now()->subDay())->delete();
        }

        return self::SUCCESS;
    }
}
