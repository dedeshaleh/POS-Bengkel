<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'buy_price_per_purchased_uom' => 'decimal:2',
        'received_price_per_purchased_uom' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function goodReceiveItems()
    {
        return $this->hasMany(GoodReceiveItem::class);
    }
}
