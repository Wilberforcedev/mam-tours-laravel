<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'action',
        'details',
        'at',
    ];

    protected $casts = [
        'details' => 'array',
        'at' => 'datetime',
    ];
}
