<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryMonitorService
{
    private const SLOW_QUERY_THRESHOLD = 100; // milliseconds

    /**
     * Register query monitoring listeners
     */
    public function register(): void
    {
        DB::listen(function ($query) {
            if ($query->time > self::SLOW_QUERY_THRESHOLD) {
                $this->logSlowQuery($query);
            }
        });
    }

    /**
     * Log slow query with context
     */
    private function logSlowQuery($query): void
    {
        $request = app()->runningInConsole() ? null : request();

        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName,
            'request_id' => $request?->header('X-Request-Id') ?? 'cli',
            'user_id' => auth()->id(),
            'url' => $request?->fullUrl() ?? 'N/A',
        ]);

        // Also send to Sentry if available
        if (app()->bound('sentry')) {
            \Sentry\captureMessage('Slow Query: ' . $query->sql, \Sentry\Severity::warning());
        }
    }

    /**
     * Get query statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_queries' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
            'slow_query_threshold' => self::SLOW_QUERY_THRESHOLD,
        ];
    }
}
