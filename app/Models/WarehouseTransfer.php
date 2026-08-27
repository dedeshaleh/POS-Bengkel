<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseTransfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }
}
