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
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'connection' => $query->connectionName,
            'request_id' => request()->id() ?? 'cli',
            'user_id' => auth()->id(),
            'url' => request()->fullUrl() ?? 'N/A',
        ]);

        // Also send to Sentry if available
        if (app()->bound('sentry')) {
            \Sentry\captureMessage('Slow Query: ' . $query->sql, [
                'level' => \Sentry\Severity::warning(),
                'extra' => [
                    'query_time' => $query->time,
                    'bindings' => $query->bindings,
                ],
            ]);
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
