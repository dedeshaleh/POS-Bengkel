<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUomConversion extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['conversion_factor' => 'decimal:2'];
}
