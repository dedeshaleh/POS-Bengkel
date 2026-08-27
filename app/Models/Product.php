<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_bundle' => 'boolean',
        'markup_value' => 'decimal:2',
        'is_active' => 'boolean',
        'on_order_qty' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function batches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function conversions()
    {
        return $this->hasMany(ProductUomConversion::class);
    }

    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id');
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_products')
            ->withPivot(['supplier_sku', 'is_active'])
            ->withTimestamps();
    }

    public function prices()
    {
        return $this->hasMany(MasterPrice::class);
    }

    public function activePrice()
    {
        return $this->hasOne(MasterPrice::class)->where('is_active', true);
    }

    public function sellingPrice(): float
    {
        $cost = (float) ($this->batches()->where('current_qty', '>', 0)->oldest()->value('base_uom_buy_price') ?? 0);

        if ($this->markup_type === 'fixed') {
            return $cost + (float) $this->markup_value;
        }

        return $cost + ($cost * ((float) $this->markup_value / 100));
    }
}
