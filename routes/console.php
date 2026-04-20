<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Thinkion Sync Scheduling
|--------------------------------------------------------------------------
|
| Sincronización diaria automática a las 02:00 AM.
| Configurable via crontab: * * * * * php /path/to/artisan schedule:run
|
*/

Schedule::command('thinkion:sync-daily')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/thinkion-cron.log'));
