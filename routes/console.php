<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Process requests hourly (expiration warnings need frequent checks)
Schedule::command('app:process-requests')->hourly();

Schedule::command('sensors:reactivate')->daily();

// Purge old records monthly (6-month retention)
Schedule::command('app:purge-old-records')->monthly();

Schedule::call(function () {
    Log::info('Le cron job fonctionne correctement.');
})->everyMinute();
