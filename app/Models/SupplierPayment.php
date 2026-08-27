<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function supplierPayable()
    {
        return $this->belongsTo(SupplierPayable::class, 'supplier_payable_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
