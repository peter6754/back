<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule daily superlike/superbooms allocation check
// Each user gets their allocation 7 days after their last reset
Schedule::command('superlikes:allocate-weekly')
    ->daily()
    ->at('00:00');

Schedule::command('superbooms:allocate-weekly')
    ->daily()
    ->at('00:00');

