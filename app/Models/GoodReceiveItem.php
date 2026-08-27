<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodReceiveItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'buy_price_per_purchased_uom' => 'decimal:2',
        'base_uom_buy_price' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function goodReceive()
    {
        return $this->belongsTo(GoodReceive::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
