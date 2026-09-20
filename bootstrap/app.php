<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'active' => EnsureAccountIsActive::class,
        ]);

        $middleware->web(append: [SecurityHeaders::class]);

        // FedaPay's server calls this endpoint with no session/CSRF token; it is
        // authenticated by re-verifying the transaction with our secret key instead.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Behind a hosting proxy / Cloudflare the real client IP and https scheme arrive in
        // X-Forwarded-* headers. Opt-in (TRUSTED_PROXIES="*" or a comma list) rather than
        // always-on, since trusting arbitrary proxies lets clients spoof their IP and
        // sidestep the rate limiters.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
