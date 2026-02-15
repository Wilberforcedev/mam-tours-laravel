<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Booking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheManager
{
    // Cache TTL constants (in seconds)
    private const TTL_CAR_LISTINGS = 300; // 5 minutes
    private const TTL_USER_SESSION = 7200; // 2 hours
    private const TTL_API_RESPONSE = 300; // 5 minutes
    private const TTL_BOOKING_DATA = 600; // 10 minutes

    /**
     * Get cars with caching
     */
    public function getCars(array $filters = []): Collection
    {
        $key = $this->buildCacheKey('cars', $filters);
        
        return Cache::tags(['cars'])->remember($key, self::TTL_CAR_LISTINGS, function () use ($filters) {
            $query = Car::query();
            
            if (isset($filters['isAvailable'])) {
                $query->where('isAvailable', $filters['isAvailable']);
            }
            
            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }
            
            return $query->get();
        });
    }

    /**
     * Get single car with caching
     */
    public function getCar(int $id): ?Car
    {
        $key = "car:{$id}";
        
        return Cache::tags(['cars'])->remember($key, self::TTL_CAR_LISTINGS, function () use ($id) {
            return Car::find($id);
        });
    }

    /**
     * Get user bookings with caching
     */
    public function getUserBookings(int $userId): Collection
    {
        $key = "user_bookings:{$userId}";
        
        return Cache::tags(['bookings', "user:{$userId}"])->remember(
            $key,
            self::TTL_BOOKING_DATA,
            function () use ($userId) {
                return Booking::where('user_id', $userId)
                    ->with('car')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        );
    }

    /**
     * Cache API response
     */
    public function cacheApiResponse(string $endpoint, $data, int $ttl = null): void
    {
        $ttl = $ttl ?? self::TTL_API_RESPONSE;
        $key = "api:{$endpoint}";
        
        Cache::put($key, $data, $ttl);
    }

    /**
     * Get cached API response
     */
    public function getCachedApiResponse(string $endpoint)
    {
        $key = "api:{$endpoint}";
        return Cache::get($key);
    }

    /**
     * Invalidate car cache
     */
    public function invalidateCarCache(int $carId = null): void
    {
        if ($carId) {
            Cache::tags(['cars'])->forget("car:{$carId}");
        } else {
            Cache::tags(['cars'])->flush();
        }
    }

    /**
     * Invalidate booking cache
     */
    public function invalidateBookingCache(int $userId = null): void
    {
        if ($userId) {
            Cache::tags(["user:{$userId}"])->flush();
        } else {
            Cache::tags(['bookings'])->flush();
        }
    }

    /**
     * Invalidate all caches
     */
    public function flushAll(): void
    {
        Cache::flush();
    }

    /**
     * Build cache key from prefix and parameters
     */
    private function buildCacheKey(string $prefix, array $params): string
    {
        if (empty($params)) {
            return $prefix;
        }
        
        ksort($params);
        return $prefix . ':' . md5(json_encode($params));
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUp(): void
    {
        // Cache all available cars
        $this->getCars(['isAvailable' => true]);
        
        // Cache cars by category
        $categories = Car::distinct()->pluck('category');
        foreach ($categories as $category) {
            $this->getCars(['category' => $category]);
        }
    }
}
