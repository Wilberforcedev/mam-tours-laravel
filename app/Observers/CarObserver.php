<?php

namespace App\Observers;

use App\Models\Car;
use App\Services\CacheManager;

class CarObserver
{
    private CacheManager $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Handle the Car "created" event.
     */
    public function created(Car $car): void
    {
        $this->cacheManager->invalidateCarCache();
    }

    /**
     * Handle the Car "updated" event.
     */
    public function updated(Car $car): void
    {
        $this->cacheManager->invalidateCarCache($car->id);
    }

    /**
     * Handle the Car "deleted" event.
     */
    public function deleted(Car $car): void
    {
        $this->cacheManager->invalidateCarCache($car->id);
    }
}
