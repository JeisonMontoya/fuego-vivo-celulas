<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:send-reminders')
    ->hourly()
    ->appendOutputTo(storage_path('logs/cron.log'));

Schedule::command('metrics:recalculate-all')
    ->dailyAt('00:05')
    ->appendOutputTo(storage_path('logs/cron.log'));
