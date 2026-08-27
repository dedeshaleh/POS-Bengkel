<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'discount_applied' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
