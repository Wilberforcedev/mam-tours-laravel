<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CacheManager;
use Illuminate\Http\Request;

class CarApiController extends Controller
{
    protected $cacheManager;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get all available cars
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'category' => 'nullable|string|max:50',
        ]);
        $perPage = (int) ($validated['per_page'] ?? 15);
        $category = $validated['category'] ?? null;
        
        $cacheKey = "api.v1.cars.index.{$perPage}.{$category}";
        
        $cars = $this->cacheManager->remember($cacheKey, 300, function () use ($perPage, $category) {
            $query = Car::where('isAvailable', true);
            
            if ($category) {
                $query->where('category', $category);
            }
            
            return $query->paginate($perPage);
        });

        return response()->json([
            'version' => 'v1',
            'data' => $cars,
        ]);
    }

    /**
     * Get a specific car
     */
    public function show($id)
    {
        $car = $this->cacheManager->remember("api.v1.car.{$id}", 300, function () use ($id) {
            return Car::find($id);
        });

        if (!$car) {
            return response()->json([
                'version' => 'v1',
                'error' => 'Car not found',
            ], 404);
        }

        return response()->json([
            'version' => 'v1',
            'data' => $car,
        ]);
    }

    /**
     * Search cars
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'seats' => 'nullable|integer|min:1',
            'category' => 'nullable|string|max:50',
        ]);

        $query = Car::where('isAvailable', true);

        if (!empty($validated['query'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('brand', 'like', "%{$validated['query']}%")
                  ->orWhere('model', 'like', "%{$validated['query']}%");
            });
        }

        if (isset($validated['min_price'])) {
            $query->where('dailyRate', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('dailyRate', '<=', $validated['max_price']);
        }

        if (isset($validated['seats'])) {
            $query->where('seats', '>=', $validated['seats']);
        }

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $cars = $query->paginate(15);

        return response()->json([
            'version' => 'v1',
            'data' => $cars,
        ]);
    }
}
