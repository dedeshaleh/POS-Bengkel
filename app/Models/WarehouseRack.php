<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseRack extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_rack_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_rack_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_rack_id');
    }
}
