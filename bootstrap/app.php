<?php

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\EnsureMemberAccess;
use App\Http\Middleware\EnsureAdminMode;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserRole::class,
            'member_access' => EnsureMemberAccess::class,
            'admin_mode' => EnsureAdminMode::class,
            'permission' => PermissionMiddleware::class,
            'revalidate' => PreventBackHistory::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, \Illuminate\Http\Request $request) {
            if (in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                // If it's a 500/503 and we're in debug mode, let Laravel show the default error page with stack trace
                if (in_array($response->getStatusCode(), [500, 503]) && config('app.debug')) {
                    return $response;
                }
                
                return \Inertia\Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            } elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'Sesi telah berakhir, silakan coba lagi.',
                ]);
            }

            return $response;
        });
    })->create();
