<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expected_qty' => 'integer',
        'actual_qty' => 'integer',
        'difference' => 'integer',
    ];

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
