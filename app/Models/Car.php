<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'carPicture',
        'brand',
        'model',
        'numberPlate',
        'dailyRate',
        'seats',
        'isAvailable',
        'category',
        'transmission',
        'fuel_type',
        'year',
        'description',
        'features',
        'is_featured',
        'view_count',
        'booking_count',
        'rating',
        'luggage_capacity',
        'doors',
    ];

    protected $casts = [
        'isAvailable' => 'boolean',
        'dailyRate' => 'integer',
        'seats' => 'integer',
        'features' => 'array',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'booking_count' => 'integer',
        'rating' => 'decimal:2',
        'year' => 'integer',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'car_id');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(VehicleMaintenanceRecord::class, 'car_id');
    }
}
