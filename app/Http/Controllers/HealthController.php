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
        
        return response()->json([
            'status' => $health['status'],
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $health['checks'],
            'timestamp' => now()->toIso8601String(),
        ], $statusCode);
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
