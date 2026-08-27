<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function batches()
    {
        return $this->hasMany(InventoryBatch::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function racks()
    {
        return $this->hasMany(WarehouseRack::class)->whereNull('parent_rack_id');
    }

    public function allRacks()
    {
        return $this->hasMany(WarehouseRack::class);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
