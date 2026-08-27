<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBatch extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'base_uom_buy_price' => 'decimal:2',
        'expired_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
