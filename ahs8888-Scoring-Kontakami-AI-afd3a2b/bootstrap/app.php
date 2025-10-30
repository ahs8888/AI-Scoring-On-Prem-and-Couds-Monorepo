<?php

use App\Http\Middleware\AdminAuthenticationMiddleware;
use App\Http\Middleware\ApiAuthenticateMiddleware;
use App\Http\Middleware\AuthenticationMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            // __DIR__ . '/../routes/web.php',
            // __DIR__ . '/../routes/admin.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        api: [
            // __DIR__ . '/../routes/web_api.php',
            // __DIR__ . '/../routes/admin_api.php',
            __DIR__ . '/../routes/api.php',
        ],
        health: '/up',
        then: function (Application $app) {
            $domain = request()->getHost();
            if (in_array($domain, ['www.' . config('services.domain.admin'), config('services.domain.admin'), 'localhost'])) {
                Route::middleware(['api'])
                    ->prefix('api-admin')
                    ->group(base_path('routes/admin_api.php'));
                Route::middleware(['web'])
                    ->group(base_path('routes/admin.php'));
            }

            if (in_array($domain, ['www.' . config('services.domain.client'), config('services.domain.client'), 'localhost'])) {
                Route::middleware(['api'])
                    ->prefix('api')
                    ->group(base_path('routes/web_api.php'));
                Route::middleware(['web'])
                    ->group(base_path('routes/web.php'));
            }
            
            // Phase 10B Routes: External API (token-authenticated)
            Route::middleware('api-auth')
                ->prefix('external/v1')
                ->group(base_path('routes/phase10b/ingest.php'));
            
            Route::middleware('api-auth')
                ->prefix('external/v1')
                ->group(base_path('routes/phase10b/decision.php'));
            
            Route::middleware('api-auth')
                ->prefix('external/v1')
                ->group(base_path('routes/phase10b/enrichment.php'));
            
            Route::middleware('api-auth')
                ->prefix('external/v1')
                ->group(base_path('routes/phase10b/telemetry.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->appendToGroup('web_api', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'me-auth' => AuthenticationMiddleware::class,
            'admin-auth' => AdminAuthenticationMiddleware::class,
            'api-auth' => ApiAuthenticateMiddleware::class,
            'idle-logout' => \App\Http\Middleware\IdleLogoutTimeout::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
