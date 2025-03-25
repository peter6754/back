<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\DebugbarMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\LogProxyRequests;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Foundation\Application;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Устанавливаем настройки cors (на всем проекте)
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class
        ]);

        // Убираем csrf верификацию (на всем проекте)
        $middleware->validateCsrfTokens(except: [
            '*',
        ]);

        // Устанавливаем middleware на админа
        $middleware->web(append: [
            DebugbarMiddleware::class,
        ]);

        // Регистрируем AuthMiddleware
        $middleware->alias([
//            'debugbar' => DebugbarMiddleware::class,
            'proxy' => LogProxyRequests::class,
            'auth' => AuthMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Clear reactions
        $schedule->command('user-reactions:prune --days=90')
            ->dailyAt('3:00');

        // Clear telescope
        $schedule->command('telescope:prune --hours=72')
            ->everySixHours();

        // Обработка очереди писем каждые 2 минуты
        $schedule->command('mail:process-queue')
            ->everyTwoMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })
    ->create();
