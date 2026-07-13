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
        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\AdminAuthenticated::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        // Trust ngrok proxy agar HTTPS/redirect berjalan benar
        $middleware->trustProxies(at: '*');
        // Exclude tema cookie dari enkripsi agar JS bisa baca langsung
        $middleware->encryptCookies(except: ['tema_tampilan']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
