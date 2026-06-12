<?php

use App\Http\Middleware\CaptureFleetActivityNotifications;
use App\Http\Middleware\EnforceUserInactivityTimeout;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', EnforceUserInactivityTimeout::class);
        $middleware->appendToGroup('web', CaptureFleetActivityNotifications::class);
        $middleware->validateCsrfTokens(except: ['session/timeout']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->report(function (QueryException $exception): void {
            $request = app()->runningInConsole() ? null : request();
            $reference = 'DB-'
                .now('Asia/Dhaka')->format('Ymd-His')
                .'-'
                .Str::upper(Str::random(6));

            Log::channel('database')->error(
                'Unhandled database exception.',
                [
                    'error_reference' => $reference,
                    'connection' => $exception->getConnectionName(),
                    'sql_state' => $exception->errorInfo[0] ?? null,
                    'database_error_code' => $exception->errorInfo[1] ?? null,
                    'database_error_message' => $exception->errorInfo[2]
                        ?? $exception->getPrevious()?->getMessage()
                        ?? $exception->getMessage(),
                    'sql' => $exception->getSql(),
                    'user_id' => $request?->user()?->id,
                    'route' => $request?->route()?->getName(),
                    'method' => $request?->method(),
                    'path' => $request?->path(),
                    'ip_address' => $request?->ip(),
                    'exception_file' => $exception->getFile(),
                    'exception_line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
        });
    })->create();
