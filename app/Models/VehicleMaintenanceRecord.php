<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenanceRecord extends Model
{
    protected $fillable = ['car_id', 'created_by', 'type', 'status', 'description', 'odometer_km', 'cost_minor', 'currency', 'scheduled_for', 'completed_on', 'insurance_expiry', 'licence_expiry', 'blocks_rental'];
    protected $casts = ['odometer_km' => 'integer', 'cost_minor' => 'integer', 'scheduled_for' => 'date', 'completed_on' => 'date', 'insurance_expiry' => 'date', 'licence_expiry' => 'date', 'blocks_rental' => 'boolean'];

    public function car() { return $this->belongsTo(Car::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
