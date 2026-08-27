<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPrice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'base_price' => 'decimal:2',
        'effective_date_start' => 'date',
        'effective_date_end' => 'date',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
