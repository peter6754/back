<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule superlike allocation check (runs every minute, allocates only if 2 minutes passed)
Schedule::command('superlikes:allocate-weekly')
    ->everyMinute();

// Schedule likes allocation for male users without subscription (runs every minute, allocates only if 4 minutes passed)
Schedule::command('likes:allocate-daily')
    ->everyMinute();

// Schedule superboom allocation check (runs every minute, allocates only if 3 minutes passed)
Schedule::command('superbooms:allocate-monthly')
    ->everyMinute();
