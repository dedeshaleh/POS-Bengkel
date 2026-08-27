<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'dpp_goods_amount' => 'decimal:2',
        'dpp_services_amount' => 'decimal:2',
        'ppn_percentage' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'withholding_tax_percentage' => 'decimal:2',
        'withholding_tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'is_government_tax_collector' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodReceives()
    {
        return $this->hasMany(GoodReceive::class);
    }
}
