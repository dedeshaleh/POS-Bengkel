<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Product;
use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function create()
    {
        return view('pos.create', [
            'products' => Product::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'tax' => (float) (AppSetting::where('setting_key', 'ppn_percentage')->value('setting_value') ?? 11),
        ]);
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['nullable', 'exists:products,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:1'],
            'selling_price' => ['required', 'array'],
            'selling_price.*' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_percentage' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:paid,partial,unpaid'],
            'payment_method' => ['nullable', 'max:50'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $inventory) {
            $sale = Sale::create([
                'receipt_number' => 'BB-' . now()->format('YmdHis'),
                'customer_id' => $data['customer_id'] ?? null,
                'status' => 'in_progress',
                'payment_status' => $data['payment_status'],
                'tax_percentage' => $data['tax_percentage'],
                'payment_method' => $data['payment_method'] ?? null,
                'sale_date' => now(),
            ]);

            $subtotal = 0;

            $hasSaleLine = false;

            foreach ($data['product_id'] as $index => $productId) {
                if (empty($productId)) {
                    continue;
                }

                $hasSaleLine = true;
                $product = Product::findOrFail($productId);
                $qty = (int) $data['qty'][$index];
                $unitPrice = (float) $data['selling_price'][$index];
                abort_if($qty < 1, 422, 'Quantity is required for every selected item.');
                $stockLines = $inventory->lockForSale($product, $qty);
                $bundleLineTotal = $product->is_bundle ? $unitPrice * $qty : null;
                $bundleLineCount = max(count($stockLines), 1);

                foreach ($stockLines as $lineIndex => $line) {
                    $lineSubtotal = $product->is_bundle
                        ? ($lineIndex === $bundleLineCount - 1
                            ? $bundleLineTotal - (($bundleLineTotal / $bundleLineCount) * ($bundleLineCount - 1))
                            : $bundleLineTotal / $bundleLineCount)
                        : $unitPrice * $line['qty'];
                    $lineUnitPrice = $lineSubtotal / $line['qty'];
                    $subtotal += $lineSubtotal;

                    $sale->items()->create([
                        'product_id' => $line['product_id'],
                        'inventory_batch_id' => $line['inventory_batch_id'],
                        'qty' => $line['qty'],
                        'buy_price' => $line['buy_price'],
                        'base_selling_price' => $lineUnitPrice,
                        'discount_amount' => 0,
                        'final_selling_price' => $lineUnitPrice,
                        'subtotal' => $lineSubtotal,
                    ]);
                }
            }

            abort_if(! $hasSaleLine, 422, 'At least one sale item is required.');

            $discount = (float) ($data['discount_amount'] ?? 0);
            $taxable = max(0, $subtotal - $discount);
            $tax = $taxable * ((float) $data['tax_percentage'] / 100);
            $grandTotal = $taxable + $tax;

            $sale->update([
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'status' => $data['payment_status'] === 'paid' ? 'completed' : 'in_progress',
            ]);

            if ($data['payment_status'] !== 'paid') {
                abort_if(empty($data['customer_id']), 422, 'Customer is required for unpaid or partial sales.');

                $paid = (float) ($data['amount_paid'] ?? 0);
                $debt = CustomerDebt::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $data['customer_id'],
                    'total_debt' => $grandTotal,
                    'amount_paid' => $paid,
                    'remaining_debt' => max(0, $grandTotal - $paid),
                    'due_date' => now()->addDays(14)->toDateString(),
                    'status' => $paid > 0 ? 'partial' : 'unpaid',
                ]);

                Customer::whereKey($data['customer_id'])->increment('total_debt', $debt->remaining_debt);
            }
        });

        return redirect()->route('dashboard')->with('status', 'Sale saved. Stock has been locked with FIFO.');
    }
}
