<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IapReceipt extends Model
{
    protected $fillable = [
        'user_id',
        'platform',
        'product_id',
        'plan_slug',
        'transaction_id',
        'purchase_token',
        'order_id',
        'receipt_data',
        'status',
        'environment',
        'price_amount',
        'price_currency',
        'purchase_at',
        'expires_at',
        'verified_at',
        'raw_response',
    ];

    protected $casts = [
        'purchase_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'verified_at'  => 'datetime',
        'raw_response' => 'array',
        'price_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}