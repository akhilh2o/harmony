<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;  // ← ADD
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar',
        'phone_code',
        'phone',
        'password',
        'provider',
        'provider_id',
        'otp_code',
        'otp_expires_at',
        'is_admin',
        'is_active',
        'is_subscribed',
        'subscription_expires_at',
        'subscription_plan',
        'dob',
        'gender',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'is_subscribed' => 'boolean',
        ];
    }

    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin == 1;  // ye sahi hai, 1/0 ke liye == kaam karta hai
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
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
