<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function index()
    {
        return response()->json(Car::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'numberPlate' => [
                'required',
                'string',
                'max:50',
                'unique:cars',
                'regex:/^(U[A-Z]{2}\s?\d{3}[A-Z]|UG\s?\d{2}\s?\d{5})$/i'
            ],
            'dailyRate' => 'required|numeric|min:1',
            'seats' => 'required|integer|min:1|max:50',
            'category' => 'nullable|string|max:50',
        ], [
            'numberPlate.regex' => 'Invalid number plate format. Use UAJ 979B (legacy) or UG 32 00042 (digital) format.'
        ]);

        // Handle car picture upload
        $carPicturePath = null;
        if ($request->hasFile('car_picture')) {
            $carPicturePath = $request->file('car_picture')->store('cars', 'public');
        }

        $car = Car::create([
            'carPicture' => $carPicturePath,
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'numberPlate' => strtoupper($validated['numberPlate']),
            'dailyRate' => $validated['dailyRate'],
            'seats' => $validated['seats'],
            'category' => $validated['category'] ?? null,
            'isAvailable' => true,
        ]);

        AuditLog::create([
            'action' => 'car.create',
            'details' => ['carId' => $car->id, 'plate' => $car->numberPlate],
            'at' => now(),
        ]);

        return response()->json(['message' => 'Car added successfully', 'car' => $car], 201);
    }

    public function show($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['error' => 'Car not found'], 404);
        }
        return response()->json($car);
    }
    

    public function update(Request $request, $id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['error' => 'Car not found'], 404);
        }

        $validated = $request->validate([
            'car_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'numberPlate' => [
                'nullable',
                'string',
                'max:50',
                'unique:cars,numberPlate,' . $id,
                'regex:/^(U[A-Z]{2}\s?\d{3}[A-Z]|UG\s?\d{2}\s?\d{5})$/i'
            ],
            'dailyRate' => 'nullable|numeric|min:1',
            'seats' => 'nullable|integer|min:1|max:50',
            'isAvailable' => 'nullable|boolean',
            'category' => 'nullable|string|max:50',
        ], [
            'numberPlate.regex' => 'Invalid number plate format. Use UAJ 979B (legacy) or UG 32 00042 (digital) format.'
        ]);

        // Handle car picture upload
        if ($request->hasFile('car_picture')) {
            // Delete old car picture if exists
            if ($car->carPicture && \Storage::disk('public')->exists($car->carPicture)) {
                \Storage::disk('public')->delete($car->carPicture);
            }

            // Store new picture
            $path = $request->file('car_picture')->store('cars', 'public');
            $validated['carPicture'] = $path;
        }

        if (isset($validated['numberPlate'])) {
            $validated['numberPlate'] = strtoupper($validated['numberPlate']);
        }

        $car->update($validated);

        return response()->json(['message' => 'Car updated successfully', 'car' => $car]);
    }

    public function destroy($id)
    {
        $car = Car::find($id);
        if (!$car) {
            return response()->json(['error' => 'Car not found'], 404);
        }

        $car->delete();
        return response()->json(['message' => 'Car deleted successfully']);
    }
}
