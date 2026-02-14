<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'user_id',
        'kyc_id',
        'customerName',
        'startDate',
        'endDate',
        'status',
        'pricing',
        'addOns',
        'payment',
        'conditionReports',
        'expiresAt',
        'confirmedAt',
        'checkedOutAt',
        'returnedAt',
        'canceledAt',
        'payment_method',
        'payment_status',
        'phone_number',
        'mobile_money_number',
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
