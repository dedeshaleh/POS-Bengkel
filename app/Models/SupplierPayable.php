<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayable extends Model
{
    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_payable_id');
    }
}
