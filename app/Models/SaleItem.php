<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'base_selling_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_selling_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function batch()
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
