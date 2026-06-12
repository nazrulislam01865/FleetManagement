<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! config('database.monitoring.slow_query_log', true)) {
            return;
        }

        $threshold = max(
            1,
            (int) config('database.monitoring.slow_query_ms', 750)
        );

        DB::listen(function (QueryExecuted $query) use ($threshold): void {
            if ($query->time < $threshold) {
                return;
            }

            $request = app()->runningInConsole() ? null : request();

            Log::channel('slow_query')->warning(
                'Slow database query detected.',
                [
                    'connection' => $query->connectionName,
                    'execution_time_ms' => round($query->time, 2),
                    'threshold_ms' => $threshold,
                    'sql' => $query->sql,
                    'route' => $request?->route()?->getName(),
                    'method' => $request?->method(),
                    'path' => $request?->path(),
                    'user_id' => $request?->user()?->id,
                ]
            );
        });
    }
}
