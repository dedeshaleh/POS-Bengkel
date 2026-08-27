<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function customerDebt()
    {
        return $this->belongsTo(CustomerDebt::class, 'customer_debt_id');
    }
}
