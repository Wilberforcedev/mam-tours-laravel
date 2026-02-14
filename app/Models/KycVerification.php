<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    use HasFactory;

    protected $table = 'kyc_verifications';

    protected $fillable = [
        'user_id',
        'id_type',
        'id_number',
        'permit_number',
        'id_document_path',
        'permit_document_path',
        'id_original_document_path',
        'permit_original_document_path',
        'status',
        'rejection_reason',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'kyc_id');
    }

    public function isVerified()
    {
        return $this->status === 'verified';
    }
}
