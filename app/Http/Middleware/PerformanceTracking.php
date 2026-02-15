<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerformanceTracking
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        // Start Sentry transaction if available
        if (app()->bound('sentry')) {
            $transactionContext = new \Sentry\Tracing\TransactionContext();
            $transactionContext->setName($request->method() . ' ' . $request->path());
            $transactionContext->setOp('http.server');
            
            \Sentry\startTransaction($transactionContext);
        }

        $response = $next($request);

        $duration = microtime(true) - $start;

        // Log slow requests (>1 second)
        if ($duration > 1.0) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => round($duration * 1000, 2) . 'ms',
                'status_code' => $response->getStatusCode(),
                'user_id' => auth()->id(),
            ]);
        }

        // Add performance headers
        $response->headers->set('X-Response-Time', round($duration * 1000, 2) . 'ms');
        $response->headers->set('X-Request-ID', $request->id());

        // Finish Sentry transaction
        if (app()->bound('sentry')) {
            $transaction = \Sentry\SentrySdk::getCurrentHub()->getTransaction();
            if ($transaction) {
                $transaction->finish();
            }
        }

        return $response;
    }
}
