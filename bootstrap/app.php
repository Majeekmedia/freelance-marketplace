<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Named aliases so routes read declaratively:
        //   ->middleware(['auth', 'account'])   gated action
        //   ->middleware(['auth', 'admin'])     admin area
        $middleware->alias([
            'account' => \App\Http\Middleware\AccountGate::class,
            'admin' => \App\Http\Middleware\AdminGate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
