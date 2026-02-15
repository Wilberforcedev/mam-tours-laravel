<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Exception;

class HealthCheckService
{
    /**
     * Check database connectivity
     */
    public function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $version = DB::select('SELECT VERSION() as version')[0]->version ?? 'unknown';
            
            return [
                'status' => 'ok',
                'message' => 'Database connected',
                'version' => $version,
                'connection' => config('database.default'),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    public function checkRedis(): array
    {
        try {
            Redis::ping();
            $info = Redis::info();
            
            return [
                'status' => 'ok',
                'message' => 'Redis connected',
                'version' => $info['redis_version'] ?? 'unknown',
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue system
     */
    public function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $size = Queue::size();
            
            // Check for failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            
            return [
                'status' => 'ok',
                'message' => 'Queue system operational',
                'connection' => $connection,
                'pending_jobs' => $size,
                'failed_jobs' => $failedJobs,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue system check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage
     */
    public function checkStorage(): array
    {
        try {
            $path = storage_path();
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);
            
            $status = $usedPercent > 90 ? 'warning' : 'ok';
            
            return [
                'status' => $status,
                'message' => 'Storage accessible',
                'free_space' => $this->formatBytes($freeSpace),
                'total_space' => $this->formatBytes($totalSpace),
                'used_percent' => $usedPercent . '%',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system
     */
    public function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test';
            
            cache()->put($key, $value, 60);
            $retrieved = cache()->get($key);
            cache()->forget($key);
            
            if ($retrieved === $value) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache system operational',
                    'driver' => config('cache.default'),
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write test failed',
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get overall system health
     */
    public function getOverallHealth(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        // Only check Redis and Queue if they're configured
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
            $checks['redis'] = $this->checkRedis();
        }

        if (config('queue.default') !== 'sync') {
            $checks['queue'] = $this->checkQueue();
        }

        $healthy = collect($checks)->every(fn($check) => $check['status'] === 'ok' || $check['status'] === 'warning');

        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
