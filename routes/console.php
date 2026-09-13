<?php

use Illuminate\Support\Facades\Schedule;

// Aggregate ad stats every hour
Schedule::command('stats:aggregate')->hourly()->withoutOverlapping();

// Also aggregate yesterday's stats at 1am (catch any late entries)
Schedule::command('stats:aggregate', ['--date' => now('Asia/Baku')->subDay()->toDateString()])->dailyAt('01:00')->timezone('Asia/Baku')->withoutOverlapping();

Schedule::command('traffic:retain --apply')->dailyAt('03:00')->timezone('Asia/Baku')->withoutOverlapping();
