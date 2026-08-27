<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\PriceCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosModuleController extends Controller
{
    public function openCashier(Request $request)
    {
        $drafts = Sale::where('status', 'in_progress')
            ->withCount('items')
            ->latest()
            ->get();

        $editingDraft = null;
        if ($request->filled('edit')) {
            $editingDraft = Sale::where('id', $request->integer('edit'))
                ->where('status', 'in_progress')
                ->with('items.product', 'customer')
                ->first();
        }

        return view('pos.open-cashier', compact('drafts', 'editingDraft'));
    }

    public function lookupProducts(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('sku', 'ilike', $term)
                        ->orWhere('barcode', 'ilike', $term)
                        ->orWhere('name', 'ilike', $term);
                });
            })
            ->orderBy('sku')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn (Product $product) => [
                'value' => $product->id,
                'label' => "{$product->sku} - {$product->name}",
                'description' => "Type: {$product->item_type_code} | UOM: {$product->base_uom_code} | Stock: {$product->total_stock}",
                'uom' => $product->base_uom_code,
                'item_type' => $product->item_type_code,
                'price' => (float) app(PriceCatalogService::class)->getActivePrice($product)?->base_price ?? 0,
                'stock' => $product->total_stock,
            ])->values(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function saveDraft(Request $request)
    {
        $data = $request->validate([
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'qty' => ['required', 'array'],
            'qty.*' => ['required', 'integer', 'min:1'],
            'selling_price' => ['required', 'array'],
            'selling_price.*' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'array'],
            'discount_amount.*' => ['nullable', 'numeric', 'min:0'],
            'header_discount' => ['nullable', 'numeric', 'min:0'],
            'header_discount_type' => ['nullable', 'in:fixed,percentage'],
            'tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'voucher_id' => ['nullable', 'exists:vouchers,id'],
            'action' => ['required', 'in:draft,pay'],
        ]);

        $voucher = !empty($data['voucher_id']) ? Voucher::find($data['voucher_id']) : null;
        $editingSale = !empty($data['sale_id']) ? Sale::find($data['sale_id']) : null;

        try {
            $sale = DB::transaction(function () use ($data, $voucher, $editingSale) {
                // If editing an existing draft, release old stock and delete old items
                if ($editingSale && $editingSale->status === 'in_progress') {
                    $editingSale->load('items.product', 'items.batch');
                    foreach ($editingSale->items as $item) {
                        if ($item->batch) {
                            $item->batch->increment('current_qty', $item->qty);
                        }
                        if ($item->product) {
                            $item->product->increment('total_stock', $item->qty);
                        }
                    }
                    $editingSale->items()->delete();

                    // Remove old voucher usage
                    if ($editingSale->voucher_id) {
                        VoucherUsage::where('sale_id', $editingSale->id)->delete();
                        Voucher::where('id', $editingSale->voucher_id)->decrement('times_used');
                    }
                }

                // Calculate subtotals server-side
                $lineSubtotals = [];
                foreach ($data['product_id'] as $index => $productId) {
                    $qty = (int) ($data['qty'][$index] ?? 1);
                    $price = (float) ($data['selling_price'][$index] ?? 0);
                    $discount = (float) ($data['discount_amount'][$index] ?? 0);
                    $lineSubtotals[] = max(0, ($qty * $price) - $discount);
                }

                $subtotalAmount = array_sum($lineSubtotals);
                $rawHeaderDisc = (float) ($data['header_discount'] ?? 0);
                $discType = $data['header_discount_type'] ?? 'fixed';
                if ($discType === 'percentage') {
                    $headerDiscount = $subtotalAmount * ($rawHeaderDisc / 100);
                } else {
                    $headerDiscount = $rawHeaderDisc;
                }
                $headerDiscount = min($subtotalAmount, $headerDiscount);
                $taxPercentage = (float) ($data['tax_percentage'] ?? 0);

                // Calculate voucher discount
                $voucherDiscount = 0;
                if ($voucher) {
                    if ($voucher->scope_type === 'item') {
                        $eligibleProductIds = $voucher->products()->pluck('products.id')->toArray();
                        $eligibleSubtotal = 0;
                        foreach ($data['product_id'] as $idx => $pid) {
                            if (in_array((int) $pid, $eligibleProductIds)) {
                                $eligibleSubtotal += $lineSubtotals[$idx];
                            }
                        }
                        $voucherDiscount = $voucher->calculateDiscount($eligibleSubtotal);
                    } else {
                        $voucherDiscount = $voucher->calculateDiscount($subtotalAmount);
                    }
                }

                $discountAmount = min($subtotalAmount, $headerDiscount + $voucherDiscount);
                $taxable = max(0, $subtotalAmount - $discountAmount);
                $taxAmount = $taxable * ($taxPercentage / 100);
                $grandTotal = max(0, $taxable + $taxAmount);

                if ($editingSale) {
                    // Update existing sale
                    $editingSale->update([
                        'customer_id' => $data['customer_id'] ?? null,
                        'subtotal_amount' => $subtotalAmount,
                        'discount_amount' => $discountAmount,
                        'tax_percentage' => $taxPercentage,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal,
                        'voucher_id' => $voucher?->id,
                    ]);
                    $sale = $editingSale;
                } else {
                    // Create new sale
                    $sale = Sale::create([
                        'receipt_number' => $this->nextReceiptNumber(),
                        'customer_id' => $data['customer_id'] ?? null,
                        'cashier_id' => auth()->id(),
                        'status' => 'in_progress',
                        'payment_status' => 'unpaid',
                        'subtotal_amount' => $subtotalAmount,
                        'discount_amount' => $discountAmount,
                        'tax_percentage' => $taxPercentage,
                        'tax_amount' => $taxAmount,
                        'grand_total' => $grandTotal,
                        'voucher_id' => $voucher?->id,
                    ]);
                }

                // Record voucher usage and increment times_used
                if ($voucher) {
                    VoucherUsage::create([
                        'voucher_id' => $voucher->id,
                        'sale_id' => $sale->id,
                        'customer_id' => $data['customer_id'] ?? null,
                        'discount_applied' => $voucherDiscount,
                    ]);
                    $voucher->increment('times_used');
                }

                foreach ($data['product_id'] as $index => $productId) {
                    $product = Product::findOrFail($productId);
                    $qty = (int) $data['qty'][$index];
                    $sellingPrice = (float) $data['selling_price'][$index];
                    $itemDiscount = (float) ($data['discount_amount'][$index] ?? 0);
                    $itemSubtotal = $lineSubtotals[$index];

                    // Lock FIFO batches - aggregate across multiple batches if needed
                    $batches = InventoryBatch::where('product_id', $productId)
                        ->where('current_qty', '>', 0)
                        ->orderBy('created_at')
                        ->lockForUpdate()
                        ->get();

                    $totalAvailable = $batches->sum('current_qty');
                    if ($totalAvailable < $qty) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    $remainingQty = $qty;
                    $buyPrice = 0;
                    $firstBatch = null;

                    foreach ($batches as $batch) {
                        if ($remainingQty <= 0) break;
                        $takeQty = min($remainingQty, $batch->current_qty);
                        $remainingQty -= $takeQty;
                        $batch->decrement('current_qty', $takeQty);
                        if (! $firstBatch) {
                            $firstBatch = $batch;
                            $buyPrice = $batch->base_uom_buy_price;
                        }
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productId,
                        'inventory_batch_id' => $firstBatch->id,
                        'qty' => $qty,
                        'buy_price' => $buyPrice,
                        'base_selling_price' => $sellingPrice,
                        'discount_amount' => $itemDiscount,
                        'final_selling_price' => $sellingPrice - $itemDiscount,
                        'subtotal' => $itemSubtotal,
                    ]);

                    $product->decrement('total_stock', $qty);
                }

                return $sale;
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($data['action'] === 'pay') {
            return redirect()->route('modules.pos.payment', $sale->id);
        }

        return redirect()->route('modules.pos.open-cashier')
            ->with('status', 'Draft saved. Stock locked.');
    }

    public function realtimeStock(Request $request)
    {
        $productId = $request->validate(['product_id' => 'required|exists:products,id'])['product_id'];

        $available = InventoryBatch::where('product_id', $productId)
            ->where('current_qty', '>', 0)
            ->sum('current_qty');

        $heldByOthers = SaleItem::whereHas('sale', function ($q) {
            $q->where('status', 'in_progress');
        })
        ->where('product_id', $productId)
        ->sum('qty');

        $effectiveStock = max(0, $available - $heldByOthers);

        $product = Product::find($productId);

        return response()->json([
            'product_id' => $productId,
            'name' => $product->name,
            'available' => $available,
            'held_by_drafts' => $heldByOthers,
            'effective_stock' => $effectiveStock,
        ]);
    }

    public function showPayment(Sale $sale)
    {
        if ($sale->status !== 'in_progress') {
            return redirect()->route('modules.pos.open-cashier')
                ->with('status', 'Sale already completed.');
        }

        $sale->load('items.product');

        return view('pos.payment', compact('sale'));
    }

    public function lookupCustomers(Request $request)
    {
        $query = Customer::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'ilike', $term)
                        ->orWhere('phone', 'ilike', $term)
                        ->orWhere('license_plate', 'ilike', $term);
                });
            })
            ->orderBy('name')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn (Customer $customer) => [
                'value' => $customer->id,
                'label' => $customer->name . ($customer->license_plate ? " ({$customer->license_plate})" : ''),
                'description' => ($customer->phone ? "Phone: {$customer->phone} | " : '') . "Debt: Rp " . number_format($customer->total_debt, 0, ',', '.'),
                'debt' => (float) $customer->total_debt,
            ])->values(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function processPayment(Request $request, Sale $sale)
    {
        if ($sale->status !== 'in_progress') {
            return redirect()->route('modules.pos.open-cashier')
                ->with('status', 'Sale already completed.');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,debt,partial'],
            'customer_id' => ['nullable', 'required_if:payment_method,debt,partial', 'exists:customers,id'],
            'amount_paid' => ['required_if:payment_method,cash,partial', 'numeric', 'min:0'],
            'debt_due_date' => ['required_if:payment_method,debt,partial', 'date', 'after_or_equal:today'],
        ]);

        // Re-validate stock before completing payment
        $stockErrors = $this->validateSaleStock($sale);
        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', implode('\n', $stockErrors));
        }

        DB::transaction(function () use ($data, $sale) {
            $grandTotal = $sale->grand_total;

            if ($data['payment_method'] === 'cash') {
                $amountPaid = (float) ($data['amount_paid'] ?? $grandTotal);

                // Cash can't create debt - if amount < total, it's just partial payment
                $paymentStatus = $amountPaid >= $grandTotal ? 'paid' : 'partial';
                $sale->update([
                    'status' => 'completed',
                    'payment_status' => $paymentStatus,
                    'payment_method' => 'cash',
                ]);

                // If cash payment is less than total, remaining is considered discount/write-off
                // No debt created for cash payments
            } elseif ($data['payment_method'] === 'debt') {
                $sale->update([
                    'status' => 'completed',
                    'payment_status' => 'unpaid',
                    'payment_method' => 'debt',
                ]);
                $this->createDebt($sale, $data['customer_id'], $grandTotal, $data['debt_due_date']);
            } elseif ($data['payment_method'] === 'partial') {
                $amountPaid = (float) $data['amount_paid'];
                $remaining = $grandTotal - $amountPaid;
                $sale->update([
                    'status' => 'completed',
                    'payment_status' => 'partial',
                    'payment_method' => 'cash',
                ]);
                if ($remaining > 0) {
                    $this->createDebt($sale, $data['customer_id'], $remaining, $data['debt_due_date']);
                }
            }
        });

        return redirect()->route('modules.pos.receipt', $sale->id);
    }

    public function showReceipt(Sale $sale)
    {
        $sale->load('items.product', 'customer', 'cashier');
        return view('pos.receipt', compact('sale'));
    }

    public function checkStock(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $errors = [];
        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            $available = InventoryBatch::where('product_id', $item['product_id'])
                ->where('current_qty', '>', 0)
                ->sum('current_qty');

            // Also subtract quantities held by other in_progress sales
            $heldByOthers = SaleItem::whereHas('sale', function ($q) {
                $q->where('status', 'in_progress');
            })
            ->where('product_id', $item['product_id'])
            ->sum('qty');

            $effectiveStock = $available - $heldByOthers;

            if ($effectiveStock < $item['qty']) {
                $errors[] = "Stok \"{$product->name}\" tidak mencukupi. Tersedia: {$effectiveStock}, diminta: {$item['qty']}.";
            }
        }

        if (!empty($errors)) {
            return response()->json(['ok' => false, 'errors' => $errors], 422);
        }

        return response()->json(['ok' => true]);
    }

    private function validateSaleStock(Sale $sale): array
    {
        $errors = [];
        $sale->load('items.product');

        foreach ($sale->items as $item) {
            $available = InventoryBatch::where('product_id', $item->product_id)
                ->where('current_qty', '>', 0)
                ->sum('current_qty');

            // The sale already locked stock (current_qty was decremented when sale was created)
            // So available already reflects this sale's deduction.
            // Check if another cashier completed a sale that consumed the same batches
            if ($available < 0) {
                $errors[] = "Stok \"{$item->product->name}\" sudah habis digunakan oleh kasir lain.";
            }
        }

        return $errors;
    }

    public function destroyDraft(Sale $sale)
    {
        if ($sale->status !== 'in_progress') {
            return redirect()->route('modules.pos.open-cashier')
                ->with('status', 'Cannot delete completed sale.');
        }

        DB::transaction(function () use ($sale) {
            $sale->load('items.product', 'items.batch');

            foreach ($sale->items as $item) {
                // Release stock back
                if ($item->batch) {
                    $item->batch->increment('current_qty', $item->qty);
                }
                if ($item->product) {
                    $item->product->increment('total_stock', $item->qty);
                }
            }

            $sale->items()->delete();
            $sale->delete();
        });

        return redirect()->route('modules.pos.open-cashier')
            ->with('status', 'Draft deleted. Stock released.');
    }

    private function createDebt(Sale $sale, ?int $customerId, float $amount, string $dueDate): void
    {
        if (! $customerId) return;

        CustomerDebt::create([
            'sale_id' => $sale->id,
            'customer_id' => $customerId,
            'total_debt' => $amount,
            'amount_paid' => 0,
            'remaining_debt' => $amount,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ]);

        Customer::where('id', $customerId)->increment('total_debt', $amount);
    }

    private function nextReceiptNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $last = Sale::where('receipt_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('receipt_number')
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function applyVoucher(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
            'product_ids' => 'array',
            'product_ids.*' => 'integer',
            'line_subtotals' => 'array',
            'line_subtotals.*' => 'numeric',
        ]);

        $voucher = Voucher::valid()
            ->where('code', strtoupper($data['code']))
            ->first();

        if (!$voucher) {
            return response()->json(['error' => 'Voucher tidak valid atau sudah kadaluarsa.'], 422);
        }

        if ($voucher->min_transaction_amount > 0 && $data['subtotal'] < $voucher->min_transaction_amount) {
            return response()->json([
                'error' => 'Minimum belanja Rp ' . number_format($voucher->min_transaction_amount, 0, ',', '.'),
            ], 422);
        }

        $eligibleSubtotal = $data['subtotal'];

        if ($voucher->scope_type === 'item') {
            $eligibleProductIds = $voucher->products()->pluck('products.id')->toArray();
            $cartProductIds = $data['product_ids'] ?? [];
            $matched = array_intersect($eligibleProductIds, $cartProductIds);

            if (empty($matched)) {
                return response()->json([
                    'error' => 'Voucher ini hanya berlaku untuk item tertentu yang tidak ada di cart.',
                ], 422);
            }

            $eligibleSubtotal = 0;
            foreach ($data['product_ids'] as $idx => $pid) {
                if (in_array((int) $pid, $eligibleProductIds)) {
                    $eligibleSubtotal += (float) ($data['line_subtotals'][$idx] ?? 0);
                }
            }
        }

        $discount = $voucher->calculateDiscount($eligibleSubtotal);

        return response()->json([
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
            'name' => $voucher->name,
            'discount_type' => $voucher->discount_type,
            'scope_type' => $voucher->scope_type,
            'discount_amount' => $discount,
            'eligible_subtotal' => $eligibleSubtotal,
        ]);
    }

    public function quickAddCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'phone' => ['nullable', 'max:20'],
            'license_plate' => ['nullable', 'max:20'],
        ]);

        $customer = Customer::create($data);

        return response()->json([
            'value' => $customer->id,
            'label' => $customer->name . ($customer->license_plate ? " ({$customer->license_plate})" : ''),
        ]);
    }
}
