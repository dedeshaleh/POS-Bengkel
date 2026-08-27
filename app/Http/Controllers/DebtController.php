<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDebt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index()
    {
        return view('debts.index', [
            'debts' => CustomerDebt::with(['customer', 'payments'])->latest('created_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function pay(Request $request, CustomerDebt $debt)
    {
        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'max:50'],
            'note' => ['nullable'],
        ]);

        DB::transaction(function () use ($data, $debt) {
            $amount = min((float) $data['amount_paid'], (float) $debt->remaining_debt);

            $debt->payments()->create([
                'amount_paid' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'note' => $data['note'] ?? null,
                'payment_date' => now(),
            ]);

            $debt->amount_paid += $amount;
            $debt->remaining_debt = max(0, (float) $debt->remaining_debt - $amount);
            $debt->status = $debt->remaining_debt <= 0 ? 'paid' : 'partial';
            $debt->save();

            Customer::whereKey($debt->customer_id)->decrement('total_debt', $amount);
        });

        return back()->with('status', 'Debt payment recorded.');
    }
}
