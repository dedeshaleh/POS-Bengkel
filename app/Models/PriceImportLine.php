<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceImportLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'base_price' => 'decimal:2',
        'effective_date_start' => 'date',
    ];

    public function batch()
    {
        return $this->belongsTo(PriceImportBatch::class, 'price_import_batch_id');
    }
}
