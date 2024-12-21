<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * Список Artisan команд, предоставляемых вашим приложением.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\ProcessMailQueue::class
    ];
    protected function schedule(Schedule $schedule)
    {

// Обработка очереди писем каждые 5 минут
        $schedule->command('mail:process-queue')
            ->everyFiveMinutes()
            ->withoutOverlapping();

// Очистка старых писем каждый день в 2:00
//        $schedule->command('mail:clean-old --days=30')
//            ->dailyAt('02:00');
    }

}
