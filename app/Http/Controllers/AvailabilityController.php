<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Services\BookingAvailabilityService;
use App\Services\PricingService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private BookingAvailabilityService $availability, private PricingService $pricing) {}

    public function check(Request $request)
    {
        $data = $request->validate([
            'car_id' => 'required|integer|exists:cars,id',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after:start_date',
        ]);
        $car = Car::findOrFail($data['car_id']);
        $available = $this->availability->isAvailable($car->id, $data['start_date'], $data['end_date']);
        $quote = $available ? $this->pricing->quote($car, $data['start_date'], $data['end_date']) : null;
        return response()->json(['available' => $available, 'quote' => $quote]);
    }
}
