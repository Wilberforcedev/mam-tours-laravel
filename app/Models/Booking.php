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

    public const ACTIVE_STATUSES = ['pending', 'reserved', 'confirmed', 'in_use'];

    public const HOLD_STATUSES = ['pending', 'reserved'];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES)
            ->where(function ($q) {
                $q->whereNotIn('status', self::HOLD_STATUSES)
                    ->orWhereNull('expiresAt')
                    ->orWhere('expiresAt', '>', now());
            });
    }

    public function scopeOverlapping($query, int $carId, $startDate, $endDate)
    {
        return $query->where('car_id', $carId)
            ->where('startDate', '<', $endDate)
            ->where('endDate', '>', $startDate);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id !== null && (int) $this->user_id === (int) $user->id;
    }

    public function isExpiredHold(): bool
    {
        return in_array($this->status, self::HOLD_STATUSES, true)
            && $this->expiresAt !== null
            && $this->expiresAt->isPast();
    }

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
