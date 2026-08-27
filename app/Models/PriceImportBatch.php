<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceImportBatch extends Model
{
    protected $guarded = [];

    public function lines()
    {
        return $this->hasMany(PriceImportLine::class);
    }
}
