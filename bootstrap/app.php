<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\LoadUserContactInfo::class,
        ]);

        $middleware->alias([
            'auth.client' => \App\Http\Middleware\AuthClient::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'verify.subdomain' => \App\Http\Middleware\ValidateSubdomain::class,
            'verify.maindomain' => \App\Http\Middleware\ValidateMainDomain::class,
            'event.access' => \App\Http\Middleware\CheckEventAccess::class,
            'venue.access' => \App\Http\Middleware\CheckVenueAccess::class,
            'event.props' => \App\Http\Middleware\LoadEventProps::class,
            'event.maintenance'  => \App\Http\Middleware\CheckEventMaintenance::class,
            'event.lock'  => \App\Http\Middleware\CheckEventLock::class,
            'user.queue' => \App\Http\Middleware\UserQueueMiddleware::class,
            'user.end.time' => \App\Http\Middleware\UserEndTimeMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
