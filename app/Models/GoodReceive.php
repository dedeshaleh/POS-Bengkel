<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodReceive extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(GoodReceiveItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
