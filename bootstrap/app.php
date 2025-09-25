<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //alias for the admin middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'evaluator' => \App\Http\Middleware\EvaluatorMiddleware::class,
            'academic_responsible' => \App\Http\Middleware\EvaluatorMiddleware::class,
        ]);
    
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
