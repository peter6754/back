<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule superlike allocation check (runs daily, allocates only if 7 days passed)
Schedule::command('superlikes:allocate-weekly')
    ->daily()
    ->at('00:00');

// Schedule daily likes allocation for male users without subscription (runs daily at midnight)
Schedule::command('likes:allocate-daily')
    ->daily()
    ->at('00:15');

// Schedule superboom allocation check (runs daily, allocates only if 30 days passed)
Schedule::command('superbooms:allocate-monthly')
    ->daily()
    ->at('00:30');
