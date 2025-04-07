<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule weekly superlike allocation every Monday at 00:00
Schedule::command('superlikes:allocate-weekly')
    ->weekly()
    ->mondays()
    ->at('00:00');

// Schedule monthly superboom allocation on the 1st of each month at 00:00
Schedule::command('superbooms:allocate-monthly')
    ->monthly()
    ->at('00:00');

