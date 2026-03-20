<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'avatar', 'phone_code', 'phone', 'password',
        'provider', 'provider_id', 'otp_code', 'otp_expires_at',
        'is_subscribed', 'subscription_expires_at', 'subscription_plan',
    ];

    protected $hidden = [
        'password', 'remember_token', 'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'password'                => 'hashed',
            'email_verified_at'       => 'datetime',
            'phone_verified_at'       => 'datetime',
            'otp_expires_at'          => 'datetime',
            'subscription_expires_at' => 'datetime',
            'is_subscribed'           => 'boolean',
        ];
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    // Check if subscription is still valid
    public function hasActiveSubscription(): bool
    {
        if (!$this->is_subscribed) return false;
        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            // Auto-expire
            $this->update(['is_subscribed' => false]);
            return false;
        }
        return true;
    }
}
