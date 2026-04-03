<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Aggregate ad stats every hour
Schedule::command('stats:aggregate')->hourly();

// Also aggregate yesterday's stats at 1am (catch any late entries)
Schedule::command('stats:aggregate', ['--date' => now()->subDay()->toDateString()])->dailyAt('01:00');
