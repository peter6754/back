<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel
{

    /**
     * @var \class-string[]
     */
    protected $commands = [
        Commands\CheckPaymentStatus::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Создание резервной копии проекта
        $schedule->command('backup:clean')->daily()->at('04:00');
        $schedule->command('backup:run')->daily()->at('05:00');

        // Проверка статусов платежей каждые 5 минут
        $schedule->command('payments:check-status')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->sendOutputTo(
                storage_path('logs/payment-status.log')
            );
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

}
