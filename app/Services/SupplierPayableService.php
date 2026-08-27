<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\SupplierPayable;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;

class SupplierPayableService
{
    public function createFromPurchase(Purchase $purchase, array $data): SupplierPayable
    {
        return DB::transaction(function () use ($purchase, $data) {
            $totalAmount = (float) ($purchase->grand_total ?: $purchase->total_amount);

            $payable = SupplierPayable::create([
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'remaining' => $totalAmount,
                'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            return $payable;
        });
    }

    public function recordPayment(SupplierPayable $payable, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($payable, $data) {
            $amount = min((float) $data['amount_paid'], (float) $payable->remaining);

            $payment = $payable->payments()->create([
                'cashier_id' => $data['cashier_id'] ?? auth()->id(),
                'amount_paid' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'payment_date' => $data['payment_date'] ?? now(),
                'note' => $data['note'] ?? null,
            ]);

            $this->recalculate($payable);

            return $payment;
        });
    }

    public function recalculate(SupplierPayable $payable): void
    {
        $totalPaid = (float) $payable->payments()->sum('amount_paid');
        $remaining = max(0, (float) $payable->total_amount - $totalPaid);

        $status = 'unpaid';
        if ($remaining <= 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }

        $payable->update([
            'amount_paid' => $totalPaid,
            'remaining' => $remaining,
            'status' => $status,
        ]);
    }
}
