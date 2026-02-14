<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConditionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'type',
        'checklist',
    ];

    protected $casts = [
        'checklist' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function photos()
    {
        return $this->hasMany(ConditionPhoto::class, 'report_id');
    }
}
