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
    private const TTL_API_RESPONSE = 300; // 5 minutes
    private const TTL_BOOKING_DATA = 600; // 10 minutes

    /**
     * Get cars with caching
     */
    public function getCars(array $filters = []): Collection
    {
        $key = $this->buildCacheKey('cars:' . $this->version('cars'), $filters);

        return Cache::remember($key, self::TTL_CAR_LISTINGS, function () use ($filters) {
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
        $key = 'car:' . $this->version('cars') . ":{$id}";

        return Cache::remember($key, self::TTL_CAR_LISTINGS, function () use ($id) {
            return Car::find($id);
        });
    }

    /**
     * Get user bookings with caching
     */
    public function getUserBookings(int $userId): Collection
    {
        $key = 'user_bookings:' . $this->version('bookings') . ':' . $this->version("bookings:{$userId}") . ":{$userId}";

        return Cache::remember($key, self::TTL_BOOKING_DATA, function () use ($userId) {
            return Booking::where('user_id', $userId)
                ->with('car')
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    public function cacheApiResponse(string $endpoint, $data, int $ttl = null): void
    {
        $ttl = $ttl ?? self::TTL_API_RESPONSE;
        $key = "api:{$endpoint}";

        Cache::put($key, $data, $ttl);
    }

    public function getCachedApiResponse(string $endpoint)
    {
        $key = "api:{$endpoint}";
        return Cache::get($key);
    }

    /**
     * Invalidate car cache. Bumping the namespace version retires every
     * cached listing at once, which works on file/database/array drivers
     * where Cache::tags() is unavailable.
     */
    public function invalidateCarCache(int $carId = null): void
    {
        $this->bump('cars');
    }

    /**
     * Invalidate booking cache for one user, or all users when no id is given.
     */
    public function invalidateBookingCache(int $userId = null): void
    {
        $this->bump($userId ? "bookings:{$userId}" : 'bookings');
    }

    public function flushAll(): void
    {
        $this->bump('cars');
        $this->bump('bookings');
    }

    private function buildCacheKey(string $prefix, array $params): string
    {
        if (empty($params)) {
            return $prefix;
        }

        ksort($params);
        return $prefix . ':' . md5(json_encode($params));
    }

    /**
     * Namespace versions are epoch counters; incrementing one orphans all
     * keys built from the previous value, letting the driver evict them.
     */
    private function version(string $namespace): int
    {
        return (int) Cache::get("cache_version:{$namespace}", 1);
    }

    private function bump(string $namespace): void
    {
        $key = "cache_version:{$namespace}";

        if (!Cache::has($key)) {
            Cache::forever($key, 1);
        }

        Cache::increment($key);
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUp(): void
    {
        $this->getCars(['isAvailable' => true]);

        $categories = Car::distinct()->pluck('category');
        foreach ($categories as $category) {
            $this->getCars(['category' => $category]);
        }
    }
}
