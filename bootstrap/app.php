<?php

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
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

        // Регистрируем AuthMiddleware
        $middleware->alias([
            'auth' => AuthMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Обработка очереди писем каждые 5 минут
        $schedule->command('mail:process-queue')
            ->everyFiveMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
