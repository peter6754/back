<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule superlike allocation check (runs every hour, allocates only if 7 days passed)
Schedule::command('superlikes:allocate-weekly')
    ->hourly();

// Schedule likes allocation for male users without subscription (runs every hour, allocates only if 1 day passed)
Schedule::command('likes:allocate-daily')
    ->hourly();

// Schedule superboom allocation check (runs every hour, allocates only if 30 days passed)
Schedule::command('superbooms:allocate-monthly')
    ->hourly();
