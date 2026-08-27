<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
