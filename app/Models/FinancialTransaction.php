<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $fillable = ['booking_id', 'user_id', 'type', 'direction', 'status', 'amount_minor', 'currency', 'provider', 'provider_reference', 'idempotency_key', 'metadata', 'occurred_at'];
    protected $casts = ['amount_minor' => 'integer', 'metadata' => 'array', 'occurred_at' => 'datetime'];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function user() { return $this->belongsTo(User::class); }
}
