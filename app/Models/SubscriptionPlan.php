<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'currency',
        'duration_type',
        'duration_days',
        'iap_product_id',
        'description',
        'features',
        'original_price',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'original_price' => 'decimal:2',
        'features'       => 'array',
        'is_popular'     => 'boolean',
        'is_active'      => 'boolean',
        'duration_days'  => 'integer',
        'sort_order'     => 'integer',
    ];

    // ── Scopes ──────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ─────────────────────────────────────────────

    /** Formatted price: ₹799 */
    public function getFormattedPriceAttribute(): string
    {
        $symbol = match ($this->currency) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency . ' ',
        };
        return $symbol . number_format($this->price, 0);
    }

    /** Savings % compared to original_price */
    public function getSavingsPercentAttribute(): ?int
    {
        if (!$this->original_price || $this->original_price <= 0) return null;
        return (int) round((1 - $this->price / $this->original_price) * 100);
    }

    /** Carbon expiry from now based on duration_days */
    public function getExpiresAt(): \Carbon\Carbon
    {
        return now()->addDays($this->duration_days);
    }
}