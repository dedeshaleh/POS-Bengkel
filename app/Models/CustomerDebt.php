<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDebt extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'total_debt' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'remaining_debt' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class, 'customer_debt_id');
    }
}
