<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_transaction_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'voucher_products');
    }

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'voucher_usages')
            ->withPivot('discount_applied', 'used_at');
    }

    public function scopeValid($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->whereColumn('times_used', '<', 'usage_limit');
    }

    public function calculateDiscount(float $baseAmount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = $baseAmount * ((float) $this->discount_value / 100);
            if ($this->max_discount_amount !== null) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            $discount = min((float) $this->discount_value, $baseAmount);
        }

        return round($discount, 2);
    }
}
