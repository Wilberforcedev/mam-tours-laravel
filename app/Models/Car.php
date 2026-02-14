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
    ];

    protected $casts = [
        'isAvailable' => 'boolean',
        'dailyRate' => 'integer',
        'seats' => 'integer',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'car_id');
    }
}
