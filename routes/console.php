<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule superlike allocation check (runs every 2 minutes)
Schedule::command('superlikes:allocate-weekly')
    ->everyTwoMinutes();

// Schedule superboom allocation check (runs every 3 minutes)
Schedule::command('superbooms:allocate-monthly')
    ->everyThreeMinutes();

