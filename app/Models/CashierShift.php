<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shift_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'counted_closing_cash' => 'decimal:2',
        'expected_closing_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Total cash sales linked to this shift.
     */
    public function totalCashSales(): float
    {
        return (float) $this->sales()
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->sum('grand_total');
    }

    /**
     * Expected cash = opening_cash + total cash sales.
     */
    public function expectedCash(): float
    {
        return (float) $this->opening_cash + $this->totalCashSales();
    }
}
