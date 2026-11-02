<?php

namespace App\Http\Controllers;

use App\Services\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    private HealthCheckService $healthCheck;

    public function __construct(HealthCheckService $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * Get system health status
     */
    public function index(): JsonResponse
    {
        $health = $this->healthCheck->getOverallHealth();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        $payload = [
            'status' => $health['status'],
            'timestamp' => now()->toIso8601String(),
        ];

        // Diagnostic detail is only for authenticated admins; probes get a
        // status code and nothing that could reveal infrastructure state.
        if (auth()->check() && auth()->user()->isAdmin()) {
            $payload['version'] = config('app.version', '1.0.0');
            $payload['environment'] = app()->environment();
            $payload['checks'] = $health['checks'];
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Simple ping endpoint
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'pong',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
