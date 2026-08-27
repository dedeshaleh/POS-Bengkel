<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Services\SupplierPayableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierPayableController extends Controller
{
    public function index()
    {
        return view('supplier_payables.index', [
            'payables' => SupplierPayable::with(['purchase', 'supplier', 'payments'])
                ->latest('created_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('supplier_payables.create', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('company_name')->get(),
            'purchases' => Purchase::with('supplier')->orderBy('purchase_date', 'desc')->get(),
        ]);
    }

    public function store(Request $request, SupplierPayableService $service)
    {
        $data = $request->validate([
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'supplier_id' => ['required_without:purchase_id', 'exists:suppliers,id'],
            'total_amount' => ['required_without:purchase_id', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable'],
        ]);

        $payable = DB::transaction(function () use ($data, $service) {
            if (! empty($data['purchase_id'])) {
                $purchase = Purchase::findOrFail($data['purchase_id']);

                return $service->createFromPurchase($purchase, [
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }

            return SupplierPayable::create([
                'purchase_id' => null,
                'supplier_id' => $data['supplier_id'],
                'total_amount' => $data['total_amount'],
                'amount_paid' => 0,
                'remaining' => $data['total_amount'],
                'due_date' => $data['due_date'],
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('supplier-payables.show', $payable)->with('status', 'Supplier payable created.');
    }

    public function show(SupplierPayable $payable)
    {
        $payable->load(['purchase', 'supplier', 'creator', 'payments.cashier']);

        return view('supplier_payables.show', compact('payable'));
    }

    public function pay(Request $request, SupplierPayable $payable, SupplierPayableService $service)
    {
        if ($request->isMethod('get')) {
            $payable->load(['purchase', 'supplier']);

            return view('supplier_payables.pay', compact('payable'));
        }

        $data = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'note' => ['nullable'],
        ]);

        $service->recordPayment($payable, [
            'amount_paid' => $data['amount_paid'],
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? now(),
            'note' => $data['note'] ?? null,
            'cashier_id' => auth()->id(),
        ]);

        return redirect()->route('supplier-payables.show', $payable)->with('status', 'Payment recorded.');
    }
}
