<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConditionPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'path',
    ];

    public function report()
    {
        return $this->belongsTo(ConditionReport::class, 'report_id');
    }
}
