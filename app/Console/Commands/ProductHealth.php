<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductHealth extends Command
{
    protected $signature = 'product:health';

    protected $description = 'Read-only product operational health check';

    public function handle(): int
    {
        try {
            DB::select('SELECT 1');
            $latest = Cache::get('stats:last_aggregate');
            $healthy = $latest && Carbon::parse($latest)->gt(now()->subHours(2));
            $this->line(json_encode(['database' => 'ok', 'aggregation' => $healthy ? 'ok' : 'stale', 'last_aggregate' => $latest, 'delivery_enabled' => config('reklam.delivery_enabled')]));

            return $healthy ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Database unavailable.');

            return self::FAILURE;
        }
    }
}
