<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([ // ← Добавьте эту секцию
        app_path('Console/Commands'),
        app_path('Console/Commands/Telegram'),
    ])
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) { // ← Добавьте это
//        $schedule->command('weather:send')
//            ->everyMinute() // Временно для теста
//            ->before(function () {
//                Log::info('Scheduling weather:send');
//            });
        $schedule->command('weather:send')
            ->dailyAt('7:00')
            ->timezone('Europe/Moscow');

        $schedule->command('weather:send')
            ->dailyAt('14:00')
            ->timezone('Europe/Moscow');

        $schedule->command('weather:send')
            ->dailyAt('16:00')
            ->timezone('Europe/Moscow');
    })
    ->withMiddleware(function (Middleware $middleware) {
//        $middleware->validateCsrfTokens(except: [
//            '/api/webhook',
//        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
