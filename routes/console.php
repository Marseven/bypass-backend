<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// Artisan::command('schedule:tasks', function (Schedule $schedule) {
//     // Tâche 1 : exécuter la commande artisan deux fois par jour (08h00 et 16h00)
//     $schedule->command('app:process-requests')->twiceDaily(8, 16);

//     // Tâche 2 : test de fonctionnement toutes les minutes
//     $schedule->call(function () {
//         Log::info('Le cron job fonctionne correctement.');
//     })->everyMinute();
// })->purpose('Planifier les tâches quotidiennes');

Schedule::command('app:process-requests')->twiceDaily(8, 15);

Schedule::command('sensors:reactivate');

Schedule::call(function () {
    Log::info('Le cron job fonctionne correctement.');
})->everyMinute();