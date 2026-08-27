<?php

namespace App\Http\Controllers;

use App\Models\GoodReceive;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PriceCatalogService;
use App\Services\TaxService;
use App\Services\UomConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        return view('purchases.index', [
            'purchases' => Purchase::with('supplier')->withCount('items')->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('purchases.create');
    }

    public function store(Request $request, InventoryService $inventory, TaxService $taxService, UomConversionService $uomService)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'max:100', 'unique:purchases,invoice_number'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,on_order,closed'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'is_government_tax_collector' => ['nullable', 'boolean'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'purchased_uom_code' => ['required', 'array'],
            'purchased_uom_code.*' => ['required', 'max:50'],
            'purchased_qty' => ['required', 'array'],
            'purchased_qty.*' => ['required', 'integer', 'min:1'],
            'qty_in_base_uom' => ['nullable', 'array'],
            'qty_in_base_uom.*' => ['nullable', 'integer', 'min:1'],
            'buy_price_per_purchased_uom' => ['required', 'array'],
            'buy_price_per_purchased_uom.*' => ['required', 'numeric', 'min:0'],
            'item_discount_type' => ['nullable', 'array'],
            'item_discount_type.*' => ['nullable', 'in:none,fixed,percentage'],
            'item_discount_value' => ['nullable', 'array'],
            'item_discount_value.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['supplier_id'])) {
            $mappedCount = DB::table('supplier_products')
                ->where('supplier_id', $data['supplier_id'])
                ->whereIn('product_id', $data['product_id'])
                ->where('is_active', true)
                ->count();

            abort_if($mappedCount !== count(array_unique($data['product_id'])), 422, 'Ada item yang belum dimapping ke supplier ini.');
        }

        DB::transaction(function () use ($data, $inventory, $taxService, $uomService) {
            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;
            $lineTotals = collect($data['product_id'])
                ->map(fn ($productId, $index) => $this->calculateLineTotals($data, $index));
            $products = Product::whereIn('id', $data['product_id'])->get()->keyBy('id');

            // Auto-calculate qty_in_base_uom from purchased_uom_code + purchased_qty
            // when not provided by the client (UOM auto-conversion).
            $baseQtys = [];
            foreach ($data['product_id'] as $index => $productId) {
                $manual = $data['qty_in_base_uom'][$index] ?? null;
                $baseQtys[$index] = $manual
                    ? (int) $manual
                    : $uomService->convertToBaseUom(
                        (int) $productId,
                        $data['purchased_uom_code'][$index],
                        (float) $data['purchased_qty'][$index],
                    );
            }

            $tax = $taxService->calculatePurchaseTax(
                $supplier,
                $lineTotals,
                $products,
                $data['product_id'],
                (float) ($data['discount_amount'] ?? 0),
                (bool) ($data['is_government_tax_collector'] ?? false),
            );

            $purchase = Purchase::create([
                'invoice_number' => $data['invoice_number'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'status' => $data['status'],
                'purchase_date' => $data['purchase_date'],
                'total_amount' => $tax['subtotal'],
                'discount_amount' => $tax['discount'],
                'dpp_goods_amount' => $tax['goods_dpp'],
                'dpp_services_amount' => $tax['services_dpp'],
                'ppn_percentage' => $tax['ppn_percentage'],
                'ppn_amount' => $tax['ppn_amount'],
                'withholding_tax_name' => $tax['withholding_tax_name'],
                'withholding_tax_percentage' => $tax['withholding_tax_percentage'],
                'withholding_tax_amount' => $tax['withholding_tax_amount'],
                'is_government_tax_collector' => (bool) ($data['is_government_tax_collector'] ?? false),
                'grand_total' => $tax['grand_total'],
            ]);

            foreach ($data['product_id'] as $index => $productId) {
                $lineTotals = $this->calculateLineTotals($data, $index);
                $item = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'purchased_uom_code' => $data['purchased_uom_code'][$index],
                    'purchased_qty' => $data['purchased_qty'][$index],
                    'received_qty' => $data['status'] === 'closed' ? $data['purchased_qty'][$index] : 0,
                    'qty_in_base_uom' => $baseQtys[$index],
                    'buy_price_per_purchased_uom' => $data['buy_price_per_purchased_uom'][$index],
                    'discount_type' => $lineTotals['discount_type'],
                    'discount_value' => $lineTotals['discount_value'],
                    'discount_amount' => $lineTotals['discount_amount'],
                    'received_price_per_purchased_uom' => $data['status'] === 'closed' ? $data['buy_price_per_purchased_uom'][$index] : null,
                    'subtotal' => $lineTotals['subtotal'],
                ]);

                // Increment on_order_qty only when PO becomes active (on_order or closed)
                if (in_array($data['status'], ['on_order', 'closed'])) {
                    Product::where('id', $productId)->increment('on_order_qty', $baseQtys[$index]);
                }

                if ($data['status'] === 'closed') {
                    $this->receiveLine($inventory, $item, (int) $data['purchased_qty'][$index], (float) $data['buy_price_per_purchased_uom'][$index]);
                }
            }
        });

        return redirect()->route('purchases.index')->with('status', 'Purchase transaction saved.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product', 'goodReceives.warehouse', 'goodReceives.items.product']);

        return view('purchases.show', compact('purchase'));
    }

    public function receiveForm(Purchase $purchase)
    {
        if ($purchase->status === 'draft') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'Draft PO belum boleh Good Receive. Ubah status PO ke On Order dulu.');
        }

        if ($purchase->status === 'closed') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'PO sudah close dan tidak bisa Good Receive lagi.');
        }

        $purchase->load(['supplier', 'items.product']);

        return view('purchases.receive', [
            'purchase' => $purchase,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function receive(Request $request, Purchase $purchase, InventoryService $inventory)
    {
        if ($purchase->status === 'draft') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'Draft PO belum boleh Good Receive.');
        }

        if ($purchase->status === 'closed') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'PO sudah close dan tidak bisa Good Receive lagi.');
        }

        $data = $request->validate([
            'delivery_note_number' => ['required', 'max:100'],
            'received_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'max:1000'],
            'receive_qty' => ['required', 'array'],
            'receive_qty.*' => ['nullable', 'integer', 'min:0'],
            'expired_date' => ['nullable', 'array'],
            'expired_date.*' => ['nullable', 'date'],
        ]);

        $hasReceivedQty = collect($data['receive_qty'])->contains(fn ($qty) => (int) $qty > 0);
        if (! $hasReceivedQty) {
            return back()->withInput()->with('status', 'Isi minimal satu qty barang yang diterima.');
        }

        DB::transaction(function () use ($purchase, $inventory, $data) {
            $purchase->load('items.product');
            $goodReceive = GoodReceive::create([
                'purchase_id' => $purchase->id,
                'warehouse_id' => $data['warehouse_id'],
                'gr_number' => $this->nextGoodReceiveNumber(),
                'delivery_note_number' => $data['delivery_note_number'],
                'received_date' => $data['received_date'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($purchase->items as $item) {
                $receiveQty = (int) ($data['receive_qty'][$item->id] ?? 0);
                if ($receiveQty <= 0) {
                    continue;
                }

                $remaining = $item->purchased_qty - $item->received_qty;
                if ($receiveQty > $remaining) {
                    abort(422, "Receive qty for {$item->product?->sku} is bigger than remaining qty.");
                }

                $receivePrice = (float) $item->buy_price_per_purchased_uom;
                $expiredDate = $data['expired_date'][$item->id] ?? null;
                $receiveMeta = $this->receiveLine($inventory, $item, $receiveQty, $receivePrice, (int) $data['warehouse_id'], $expiredDate);

                $goodReceive->items()->create([
                    'purchase_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'received_qty' => $receiveQty,
                    'received_qty_in_base_uom' => $receiveMeta['base_qty'],
                    'expired_date' => $expiredDate,
                    'buy_price_per_purchased_uom' => $receivePrice,
                    'base_uom_buy_price' => $receiveMeta['base_price'],
                ]);

                $item->update([
                    'received_qty' => $item->received_qty + $receiveQty,
                    'received_price_per_purchased_uom' => $receivePrice,
                ]);

                // Decrement on_order_qty when goods are received
                $baseQtyDecrement = max(1, (int) round($receiveQty * ($item->qty_in_base_uom / max(1, $item->purchased_qty))));
                Product::where('id', $item->product_id)->decrement('on_order_qty', $baseQtyDecrement);
            }

            $purchase->refresh()->load('items');
            $isClosed = $purchase->items->every(fn ($item) => $item->received_qty >= $item->purchased_qty);
            $purchase->update(['status' => $isClosed ? 'closed' : 'on_order']);
        });

        return redirect()->route('purchases.show', $purchase)->with('status', 'Good Receive saved.');
    }

    public function activate(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'Hanya PO status draft yg bisa diaktifkan.');
        }

        DB::transaction(function () use ($purchase) {
            $purchase->load('items');
            $purchase->update(['status' => 'on_order']);

            // Increment on_order_qty for each item when draft → on_order
            foreach ($purchase->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('on_order_qty', (int) $item->qty_in_base_uom);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('status', 'PO berhasil diaktifkan menjadi On Order.');
    }

    public function edit(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'Hanya PO status draft yg bisa diedit.');
        }

        $purchase->load(['supplier', 'items.product']);

        return view('purchases.edit', compact('purchase'));
    }

    public function update(Request $request, Purchase $purchase, TaxService $taxService, UomConversionService $uomService)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'Hanya PO status draft yg bisa diedit.');
        }

        $data = $request->validate([
            'invoice_number' => ['required', 'max:100', 'unique:purchases,invoice_number,' . $purchase->id],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'status' => ['required', 'in:draft,on_order,closed'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'is_government_tax_collector' => ['nullable', 'boolean'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'purchased_uom_code' => ['required', 'array'],
            'purchased_uom_code.*' => ['required', 'max:50'],
            'purchased_qty' => ['required', 'array'],
            'purchased_qty.*' => ['required', 'integer', 'min:1'],
            'qty_in_base_uom' => ['nullable', 'array'],
            'qty_in_base_uom.*' => ['nullable', 'integer', 'min:1'],
            'buy_price_per_purchased_uom' => ['required', 'array'],
            'buy_price_per_purchased_uom.*' => ['required', 'numeric', 'min:0'],
            'item_discount_type' => ['nullable', 'array'],
            'item_discount_type.*' => ['nullable', 'in:none,fixed,percentage'],
            'item_discount_value' => ['nullable', 'array'],
            'item_discount_value.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['supplier_id'])) {
            $mappedCount = DB::table('supplier_products')
                ->where('supplier_id', $data['supplier_id'])
                ->whereIn('product_id', $data['product_id'])
                ->where('is_active', true)
                ->count();

            abort_if($mappedCount !== count(array_unique($data['product_id'])), 422, 'Ada item yang belum dimapping ke supplier ini.');
        }

        DB::transaction(function () use ($purchase, $data, $taxService, $uomService) {
            $supplier = ! empty($data['supplier_id']) ? Supplier::find($data['supplier_id']) : null;
            $lineTotals = collect($data['product_id'])
                ->map(fn ($productId, $index) => $this->calculateLineTotals($data, $index));
            $products = Product::whereIn('id', $data['product_id'])->get()->keyBy('id');

            // Auto-calculate qty_in_base_uom from purchased_uom_code + purchased_qty
            // when not provided by the client (UOM auto-conversion).
            $baseQtys = [];
            foreach ($data['product_id'] as $index => $productId) {
                $manual = $data['qty_in_base_uom'][$index] ?? null;
                $baseQtys[$index] = $manual
                    ? (int) $manual
                    : $uomService->convertToBaseUom(
                        (int) $productId,
                        $data['purchased_uom_code'][$index],
                        (float) $data['purchased_qty'][$index],
                    );
            }

            $tax = $taxService->calculatePurchaseTax(
                $supplier,
                $lineTotals,
                $products,
                $data['product_id'],
                (float) ($data['discount_amount'] ?? 0),
                (bool) ($data['is_government_tax_collector'] ?? false),
            );

            // Delete old items
            $purchase->items()->delete();

            // Update purchase header
            $purchase->update([
                'invoice_number' => $data['invoice_number'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'status' => $data['status'],
                'purchase_date' => $data['purchase_date'],
                'total_amount' => $tax['subtotal'],
                'discount_amount' => $tax['discount'],
                'dpp_goods_amount' => $tax['goods_dpp'],
                'dpp_services_amount' => $tax['services_dpp'],
                'ppn_percentage' => $tax['ppn_percentage'],
                'ppn_amount' => $tax['ppn_amount'],
                'withholding_tax_name' => $tax['withholding_tax_name'],
                'withholding_tax_percentage' => $tax['withholding_tax_percentage'],
                'withholding_tax_amount' => $tax['withholding_tax_amount'],
                'is_government_tax_collector' => (bool) ($data['is_government_tax_collector'] ?? false),
                'grand_total' => $tax['grand_total'],
            ]);

            // Re-create items
            foreach ($data['product_id'] as $index => $productId) {
                $lineTotals = $this->calculateLineTotals($data, $index);
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $productId,
                    'purchased_uom_code' => $data['purchased_uom_code'][$index],
                    'purchased_qty' => $data['purchased_qty'][$index],
                    'received_qty' => 0,
                    'qty_in_base_uom' => $baseQtys[$index],
                    'buy_price_per_purchased_uom' => $data['buy_price_per_purchased_uom'][$index],
                    'discount_type' => $lineTotals['discount_type'],
                    'discount_value' => $lineTotals['discount_value'],
                    'discount_amount' => $lineTotals['discount_amount'],
                    'subtotal' => $lineTotals['subtotal'],
                ]);
            }
        });

        return redirect()->route('purchases.show', $purchase)->with('status', 'PO draft berhasil diperbarui.');
    }

    public function close(Purchase $purchase)
    {
        if ($purchase->status === 'closed') {
            return redirect()->route('purchases.show', $purchase)->with('status', 'PO sudah close.');
        }

        $purchase->update(['status' => 'closed']);

        return redirect()->route('purchases.show', $purchase)->with('status', 'PO berhasil di-close. Tidak bisa Good Receive lagi.');
    }

    public function printPo(Purchase $purchase)
    {
        $purchase->load(['supplier', 'items.product']);

        return view('purchases.print', compact('purchase'));
    }

    public function printGoodReceive(Purchase $purchase, GoodReceive $goodReceive)
    {
        abort_unless($goodReceive->purchase_id === $purchase->id, 404);

        $goodReceive->load(['purchase.supplier', 'warehouse', 'items.product', 'items.purchaseItem']);

        return view('purchases.good-receive-print', compact('purchase', 'goodReceive'));
    }

    public function lookupProducts(Request $request)
    {
        if (! $request->filled('supplier_id')) {
            return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1]);
        }

        $query = Product::query()
            ->where('is_active', true)
            ->where('is_bundle', false)
            ->whereHas('suppliers', function ($query) use ($request) {
                $query->where('suppliers.id', $request->integer('supplier_id'))
                    ->where('supplier_products.is_active', true);
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('sku', 'ilike', $term)
                        ->orWhere('name', 'ilike', $term)
                        ->orWhere('base_uom_code', 'ilike', $term);
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
            ])->values(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function lookupSuppliers(Request $request)
    {
        $query = Supplier::query()
            ->where('is_active', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->q . '%';
                $query->where(function ($query) use ($term) {
                    $query->where('company_name', 'ilike', $term)
                        ->orWhere('contact_person', 'ilike', $term)
                        ->orWhere('phone', 'ilike', $term)
                        ->orWhere('tax_id_npwp', 'ilike', $term);
                });
            })
            ->orderBy('company_name')
            ->paginate(8);

        return response()->json([
            'data' => $query->getCollection()->map(fn (Supplier $supplier) => [
                'value' => $supplier->id,
                'label' => $supplier->company_name,
                'description' => ($supplier->is_ppn_enabled ? 'PKP PPN ' . $supplier->ppn_percentage . '%' : 'Non-PKP') . ' | ' . str($supplier->entity_type ?? 'corporate')->title(),
                'ppn' => $supplier->is_ppn_enabled ? (float) $supplier->ppn_percentage : 0,
                'entity_type' => $supplier->entity_type ?? 'corporate',
                'has_npwp' => filled($supplier->tax_id_npwp),
                'pph21' => (float) ($supplier->pph21_percentage ?? 5),
            ])->values(),
            'current_page' => $query->currentPage(),
            'last_page' => $query->lastPage(),
        ]);
    }

    public function lastPrice(Request $request, Product $product, PriceCatalogService $prices)
    {
        $source = 'none';
        $price = PurchaseItem::query()
            ->where('product_id', $product->id)
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->whereHas('purchase', fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')));
            })
            ->whereNotNull('received_price_per_purchased_uom')
            ->latest('id')
            ->value('received_price_per_purchased_uom');
        if ($price !== null) {
            $source = 'Last Supplier Receive Price';
        }

        $price ??= PurchaseItem::query()
            ->where('product_id', $product->id)
            ->when($request->filled('supplier_id'), function ($query) use ($request) {
                $query->whereHas('purchase', fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')));
            })
            ->latest('id')
            ->value('buy_price_per_purchased_uom');
        if ($source === 'none' && $price !== null) {
            $source = 'Last Supplier PO Price';
        }

        if ($price === null) {
            $activePrice = $prices->getActivePrice($product);
            if ($activePrice) {
                $price = $activePrice->base_price;
                $source = 'Active Master Price';
            }
        }

        $price ??= $product->batches()
            ->latest('id')
            ->value('base_uom_buy_price');
        if ($source === 'none' && $price !== null) {
            $source = 'Last Inventory Batch';
        }

        return response()->json(['price' => (float) ($price ?? 0), 'uom' => $product->base_uom_code, 'source' => $source]);
    }

    /**
     * Return available UOM options for a product (base + conversions).
     * Used by PO UI to render a UOM dropdown per line.
     */
    public function lookupUoms(Product $product, UomConversionService $uomService)
    {
        return response()->json([
            'product_id' => $product->id,
            'base_uom' => $product->base_uom_code,
            'uoms' => $uomService->getAvailableUoms($product->id),
        ]);
    }

    private function receiveLine(InventoryService $inventory, PurchaseItem $item, int $receivedQty, float $receivedPrice, ?int $warehouseId = null, ?string $expiredDate = null): array
    {
        $factor = $item->purchased_qty > 0 ? ($item->qty_in_base_uom / $item->purchased_qty) : 1;
        $receivedBaseQty = max(1, (int) round($receivedQty * $factor));
        $basePrice = ($receivedQty * $receivedPrice) / $receivedBaseQty;

        $inventory->receive($item->product, $receivedBaseQty, $basePrice, $item->id, $warehouseId, $expiredDate);

        return ['base_qty' => $receivedBaseQty, 'base_price' => $basePrice];
    }

    private function calculateLineTotals(array $data, int $index): array
    {
        $gross = (float) $data['purchased_qty'][$index] * (float) $data['buy_price_per_purchased_uom'][$index];
        $discountType = $data['item_discount_type'][$index] ?? 'none';
        $discountValue = (float) ($data['item_discount_value'][$index] ?? 0);
        $discountAmount = match ($discountType) {
            'percentage' => $gross * ($discountValue / 100),
            'fixed' => $discountValue,
            default => 0,
        };
        $discountAmount = min($gross, max(0, $discountAmount));

        return [
            'gross' => $gross,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'subtotal' => max(0, $gross - $discountAmount),
        ];
    }

    private function nextGoodReceiveNumber(): string
    {
        $prefix = 'GR-' . now()->format('Ymd') . '-';
        $lastNumber = GoodReceive::where('gr_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('gr_number')
            ->value('gr_number');

        $next = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
