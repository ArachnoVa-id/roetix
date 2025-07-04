<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule the command to run every minute
app(Schedule::class)->command('orders:update-expired')->everyFiveMinutes();
app(Schedule::class)->command('event:adjust-users')->everyFiveMinutes();
