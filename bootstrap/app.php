<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Router;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function (Router $router) {
            // Load web routes (API routes moved here to remove /api prefix)
            require __DIR__ . '/../routes/web.php';
        },
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware can go here if needed
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception handling here if needed
    })
    ->create();