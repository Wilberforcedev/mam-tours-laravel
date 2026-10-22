<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $hidden = ['idNumber', 'idDocument', 'payment'];

    protected $fillable = [
        'car_id',
        'user_id',
        'kyc_id',
        'customerName',
        'customerEmail',
        'customerPhone',
        'startDate',
        'endDate',
        'status',
        'pricing',
        'totalPrice',
        'addOns',
        'payment',
        'conditionReports',
        'expiresAt',
        'confirmedAt',
        'checkedOutAt',
        'returnedAt',
        'canceledAt',
        'paymentMethod',
        'payment_method',
        'payment_status',
        'phone_number',
        'mobileMoneyNumber',
        'mobile_money_number',
        'idType',
        'idNumber',
        'idDocument',
        'pickup_odometer_km',
        'return_odometer_km',
        'pickup_fuel_percent',
        'return_fuel_percent',
        'pickup_condition_notes',
        'return_condition_notes',
        'pickup_time',
        'return_time',
    ];

    protected $casts = [
        'pricing' => 'array',
        'addOns' => 'array',
        'payment' => 'array',
        'conditionReports' => 'array',
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'expiresAt' => 'datetime',
        'confirmedAt' => 'datetime',
        'checkedOutAt' => 'datetime',
        'returnedAt' => 'datetime',
        'canceledAt' => 'datetime',
        'pickup_odometer_km' => 'integer',
        'return_odometer_km' => 'integer',
        'pickup_fuel_percent' => 'integer',
        'return_fuel_percent' => 'integer',
    ];

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kyc()
    {
        return $this->belongsTo(KycVerification::class, 'kyc_id');
    }

    public function conditionReports()
    {
        return $this->hasMany(ConditionReport::class, 'booking_id');
    }
}
