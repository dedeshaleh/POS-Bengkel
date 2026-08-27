<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'estimated_completion' => 'date',
        'total_amount' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_subtotal' => 'decimal:2',
        'other_cost' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function items()
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
