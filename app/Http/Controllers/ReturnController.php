<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\Supplier;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    // ── Purchase Returns ──

    public function purchaseIndex()
    {
        $returns = PurchaseReturn::with(['supplier', 'purchase', 'items.product'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('returns.purchases.index', compact('returns'));
    }

    public function purchaseCreate(Request $request)
    {
        $purchases = Purchase::with('supplier')->where('status', 'closed')->latest()->limit(50)->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('company_name')->get();

        $batches = InventoryBatch::with('product')
            ->where('current_qty', '>', 0)
            ->latest()
            ->limit(100)
            ->get();

        return view('returns.purchases.create', compact('purchases', 'suppliers', 'batches'));
    }

    public function purchaseStore(Request $request, ReturnService $service)
    {
        $data = $request->validate([
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'max:200'],
            'note' => ['nullable', 'max:1000'],
            'status' => ['required', 'in:draft,approved'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.buy_price' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'max:200'],
        ]);

        DB::transaction(function () use ($data, $service) {
            $return = PurchaseReturn::create([
                'purchase_id' => $data['purchase_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'return_number' => $this->nextReturnNumber('PR'),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = (float) $item['buy_price'] * (int) $item['qty'];
                $total += $subtotal;

                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'inventory_batch_id' => $item['inventory_batch_id'] ?? null,
                    'qty' => $item['qty'],
                    'buy_price' => $item['buy_price'],
                    'subtotal' => $subtotal,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            $return->update(['total_amount' => $total]);

            if ($data['status'] === 'approved') {
                $service->processPurchaseReturn($return->fresh());
            }
        });

        return redirect()->route('returns.purchases.index')->with('status', 'Purchase return saved.');
    }

    public function purchaseShow(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'purchase', 'items.product', 'items.batch', 'creator']);
        return view('returns.purchases.show', ['return' => $purchaseReturn]);
    }

    public function purchaseApprove(PurchaseReturn $purchaseReturn, ReturnService $service)
    {
        if ($purchaseReturn->status === 'approved') {
            return back()->with('status', 'Return already approved.');
        }

        $service->processPurchaseReturn($purchaseReturn);

        return redirect()->route('returns.purchases.show', $purchaseReturn)->with('status', 'Return approved. Stock deducted.');
    }

    // ── Sales Returns ──

    public function salesIndex()
    {
        $returns = SalesReturn::with(['customer', 'sale', 'items.product'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('returns.sales.index', compact('returns'));
    }

    public function salesCreate()
    {
        $sales = Sale::with('customer')->where('status', 'completed')->latest()->limit(50)->get();
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('is_active', true)->where('is_bundle', false)->orderBy('sku')->get();

        return view('returns.sales.create', compact('sales', 'customers', 'products'));
    }

    public function salesStore(Request $request, ReturnService $service)
    {
        $data = $request->validate([
            'sale_id' => ['nullable', 'exists:sales,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'max:200'],
            'note' => ['nullable', 'max:1000'],
            'status' => ['required', 'in:draft,approved'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.buy_price' => ['required', 'numeric', 'min:0'],
            'items.*.selling_price' => ['required', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'max:200'],
        ]);

        DB::transaction(function () use ($data, $service) {
            $return = SalesReturn::create([
                'sale_id' => $data['sale_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'return_number' => $this->nextReturnNumber('SR'),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'note' => $data['note'] ?? null,
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $subtotal = (float) $item['selling_price'] * (int) $item['qty'];
                $total += $subtotal;

                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'inventory_batch_id' => $item['inventory_batch_id'] ?? null,
                    'qty' => $item['qty'],
                    'buy_price' => $item['buy_price'],
                    'selling_price' => $item['selling_price'],
                    'subtotal' => $subtotal,
                    'reason' => $item['reason'] ?? null,
                ]);
            }

            $return->update(['total_amount' => $total]);

            if ($data['status'] === 'approved') {
                $service->processSalesReturn($return->fresh());
            }
        });

        return redirect()->route('returns.sales.index')->with('status', 'Sales return saved.');
    }

    public function salesShow(SalesReturn $salesReturn)
    {
        $salesReturn->load(['customer', 'sale', 'items.product', 'items.batch', 'creator']);
        return view('returns.sales.show', ['return' => $salesReturn]);
    }

    public function salesApprove(SalesReturn $salesReturn, ReturnService $service)
    {
        if ($salesReturn->status === 'approved') {
            return back()->with('status', 'Return already approved.');
        }

        $service->processSalesReturn($salesReturn);

        return redirect()->route('returns.sales.show', $salesReturn)->with('status', 'Return approved. Stock restored.');
    }

    // ── Helpers ──

    private function nextReturnNumber(string $prefix): string
    {
        $fullPrefix = $prefix . '-' . now()->format('Ymd') . '-';
        $last = PurchaseReturn::where('return_number', 'like', $fullPrefix . '%')
            ->lockForUpdate()
            ->orderByDesc('return_number')
            ->value('return_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $fullPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
