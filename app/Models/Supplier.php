<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'is_ppn_enabled' => 'boolean',
        'ppn_percentage' => 'decimal:2',
        'pph21_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function supplierProducts()
    {
        return $this->hasMany(SupplierProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'supplier_products')
            ->withPivot(['supplier_sku', 'is_active'])
            ->withTimestamps();
    }
}
