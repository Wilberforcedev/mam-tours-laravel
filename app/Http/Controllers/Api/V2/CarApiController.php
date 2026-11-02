<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CacheManager;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class CarApiController extends Controller
{
    protected $cacheManager;
    protected $analyticsService;

    public function __construct(CacheManager $cacheManager, AnalyticsService $analyticsService)
    {
        $this->cacheManager = $cacheManager;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get all available cars with enhanced filtering
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'category' => 'nullable|string|max:50',
            'sort_by' => 'sometimes|in:created_at,brand,model,dailyRate,seats',
            'sort_order' => 'sometimes|in:asc,desc',
        ]);
        $perPage = (int) ($validated['per_page'] ?? 15);
        $category = $validated['category'] ?? null;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        
        $cacheKey = "api.v2.cars.index.{$perPage}.{$category}.{$sortBy}.{$sortOrder}";
        
        $cars = $this->cacheManager->remember($cacheKey, 300, function () use ($perPage, $category, $sortBy, $sortOrder) {
            $query = Car::where('isAvailable', true);
            
            if ($category) {
                $query->where('category', $category);
            }
            
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage);
        });

        return response()->json([
            'version' => 'v2',
            'data' => $cars->items(),
            'meta' => [
                'current_page' => $cars->currentPage(),
                'per_page' => $cars->perPage(),
                'total' => $cars->total(),
                'last_page' => $cars->lastPage(),
            ],
            'links' => [
                'first' => $cars->url(1),
                'last' => $cars->url($cars->lastPage()),
                'prev' => $cars->previousPageUrl(),
                'next' => $cars->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific car with analytics tracking
     */
    public function show($id)
    {
        $car = $this->cacheManager->remember("api.v2.car.{$id}", 300, function () use ($id) {
            return Car::find($id);
        });

        if (!$car) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'CAR_NOT_FOUND',
                    'message' => 'The requested car could not be found',
                ],
            ], 404);
        }

        // Track car view in v2
        $this->analyticsService->trackCarView($car->id, [
            'api_version' => 'v2',
            'brand' => $car->brand,
            'model' => $car->model,
        ]);

        return response()->json([
            'version' => 'v2',
            'data' => $car,
        ]);
    }

    /**
     * Advanced search with more filters
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'min_seats' => 'nullable|integer|min:1',
            'max_seats' => 'nullable|integer|max:50',
            'category' => 'nullable|string|max:50',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after:available_from',
        ]);

        $query = Car::where('isAvailable', true);

        if (!empty($validated['query'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('brand', 'like', "%{$validated['query']}%")
                  ->orWhere('model', 'like', "%{$validated['query']}%")
                  ->orWhere('numberPlate', 'like', "%{$validated['query']}%");
            });
        }

        if (isset($validated['min_price'])) {
            $query->where('dailyRate', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('dailyRate', '<=', $validated['max_price']);
        }

        if (isset($validated['min_seats'])) {
            $query->where('seats', '>=', $validated['min_seats']);
        }

        if (isset($validated['max_seats'])) {
            $query->where('seats', '<=', $validated['max_seats']);
        }

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $cars = $query->paginate(15);

        // Track search
        $this->analyticsService->trackSearch($validated['query'] ?? '', $cars->total());

        return response()->json([
            'version' => 'v2',
            'data' => $cars->items(),
            'meta' => [
                'current_page' => $cars->currentPage(),
                'per_page' => $cars->perPage(),
                'total' => $cars->total(),
                'last_page' => $cars->lastPage(),
            ],
        ]);
    }
}
