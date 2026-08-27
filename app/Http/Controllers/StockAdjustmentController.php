<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use RuntimeException;

class StockAdjustmentController extends Controller
{
    public function index()
    {
        return view('stock_adjustments.index', [
            'stockAdjustments' => StockAdjustment::with(['warehouse', 'creator'])->withCount('items')->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('stock_adjustments.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'batches' => InventoryBatch::with(['product', 'warehouse'])->where('current_qty', '>', 0)->orderBy('created_at')->get(),
        ]);
    }

    public function store(Request $request, StockAdjustmentService $service)
    {
        $data = $request->validate($this->rules());

        $stockAdjustment = $this->saveAdjustment(new StockAdjustment(), $data, $service);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment saved.');
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['warehouse', 'creator', 'items.product', 'items.inventoryBatch']);

        return view('stock_adjustments.show', compact('stockAdjustment'));
    }

    public function edit(StockAdjustment $stockAdjustment)
    {
        if ($stockAdjustment->status !== 'draft') {
            return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('error', 'Only draft adjustments can be edited.');
        }

        $stockAdjustment->load('items.product', 'items.inventoryBatch');

        return view('stock_adjustments.edit', [
            'stockAdjustment' => $stockAdjustment,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'batches' => InventoryBatch::with(['product', 'warehouse'])->where('current_qty', '>', 0)->orderBy('created_at')->get(),
        ]);
    }

    public function update(Request $request, StockAdjustment $stockAdjustment, StockAdjustmentService $service)
    {
        if ($stockAdjustment->status !== 'draft') {
            return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('error', 'Only draft adjustments can be updated.');
        }

        $data = $request->validate($this->rules($stockAdjustment));

        $stockAdjustment = $this->saveAdjustment($stockAdjustment, $data, $service);

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment updated.');
    }

    public function showFinalize(StockAdjustment $stockAdjustment)
    {
        $stockAdjustment->load(['warehouse', 'creator', 'items.product', 'items.inventoryBatch']);

        return view('stock_adjustments.finalize', compact('stockAdjustment'));
    }

    public function finalize(StockAdjustment $stockAdjustment, StockAdjustmentService $service)
    {
        try {
            $service->finalize($stockAdjustment);
        } catch (RuntimeException $e) {
            return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('status', 'Stock adjustment finalized and stock updated.');
    }

    private function saveAdjustment(StockAdjustment $stockAdjustment, array $data, StockAdjustmentService $service): StockAdjustment
    {
        $isNew = $stockAdjustment->exists === false;

        $stockAdjustment->fill([
            'warehouse_id' => $data['warehouse_id'],
            'adjustment_date' => $data['adjustment_date'],
            'reason' => $data['reason'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($isNew) {
            $stockAdjustment->status = 'draft';
            $stockAdjustment->created_by = auth()->id();
        }

        $stockAdjustment->save();

        $stockAdjustment->items()->delete();

        foreach ($data['items'] ?? [] as $item) {
            $expectedQty = (int) ($item['expected_qty'] ?? 0);
            $actualQty = (int) ($item['actual_qty'] ?? 0);
            $difference = $service->calculateDifference($expectedQty, $actualQty);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $stockAdjustment->id,
                'product_id' => $item['product_id'],
                'inventory_batch_id' => $item['inventory_batch_id'] ?? null,
                'expected_qty' => $expectedQty,
                'actual_qty' => $actualQty,
                'difference' => $difference,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return $stockAdjustment;
    }

    private function rules(?StockAdjustment $stockAdjustment = null): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.inventory_batch_id' => ['nullable', 'exists:inventory_batches,id'],
            'items.*.expected_qty' => ['required', 'integer', 'min:0'],
            'items.*.actual_qty' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
