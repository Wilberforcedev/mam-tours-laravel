<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'sms_notifications',
        'email_notifications',
        'profile_picture',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'sms_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'two_factor_recovery_codes' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    public function isLocked()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function lockAccount($minutes = 30)
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'failed_login_attempts' => 0
        ]);
    }

    public function unlockAccount()
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }

    public function incrementFailedAttempts()
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }

    public function resetFailedAttempts()
    {
        $this->update(['failed_login_attempts' => 0]);
    }

    public function updateLoginInfo($ip)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'failed_login_attempts' => 0
        ]);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class, 'user_id');
    }

    public function kyc()
    {
        return $this->hasOne(KycVerification::class);
    }

    public function isKycVerified()
    {
        return $this->kyc && $this->kyc->isVerified();
    }

    // API Token management
    public function createApiToken($name = 'api-token')
    {
        return $this->createToken($name)->plainTextToken;
    }

    public function revokeAllTokens()
    {
        $this->tokens()->delete();
    }
}

