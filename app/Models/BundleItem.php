<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}
