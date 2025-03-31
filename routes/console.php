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
