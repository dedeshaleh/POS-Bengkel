<?php

namespace App\Http\Controllers;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use App\Services\WarehouseTransferService;
use Illuminate\Http\Request;
use RuntimeException;

class WarehouseTransferController extends Controller
{
    public function index()
    {
        return view('warehouse_transfers.index', [
            'transfers' => WarehouseTransfer::with(['fromWarehouse', 'toWarehouse', 'creator'])
                ->withCount('items')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('warehouse_transfers.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'batches' => InventoryBatch::with(['product', 'warehouse'])
                ->where('current_qty', '>', 0)
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request, WarehouseTransferService $service)
    {
        $data = $request->validate($this->rules());

        $transfer = $this->saveTransfer(new WarehouseTransfer(), $data, $service);

        return redirect()->route('warehouse-transfers.show', $transfer)->with('status', 'Warehouse transfer saved.');
    }

    public function show(WarehouseTransfer $warehouseTransfer)
    {
        $warehouseTransfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'items.product', 'items.inventoryBatch']);

        return view('warehouse_transfers.show', compact('warehouseTransfer'));
    }

    public function edit(WarehouseTransfer $warehouseTransfer)
    {
        if ($warehouseTransfer->status !== 'draft') {
            return redirect()->route('warehouse-transfers.show', $warehouseTransfer)->with('error', 'Only draft transfers can be edited.');
        }

        $warehouseTransfer->load('items.product', 'items.inventoryBatch');

        return view('warehouse_transfers.edit', [
            'warehouseTransfer' => $warehouseTransfer,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'batches' => InventoryBatch::with(['product', 'warehouse'])
                ->where('current_qty', '>', 0)
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    public function update(Request $request, WarehouseTransfer $warehouseTransfer, WarehouseTransferService $service)
    {
        if ($warehouseTransfer->status !== 'draft') {
            return redirect()->route('warehouse-transfers.show', $warehouseTransfer)->with('error', 'Only draft transfers can be updated.');
        }

        $data = $request->validate($this->rules($warehouseTransfer));

        $transfer = $this->saveTransfer($warehouseTransfer, $data, $service);

        return redirect()->route('warehouse-transfers.show', $transfer)->with('status', 'Warehouse transfer updated.');
    }

    public function showFinalize(WarehouseTransfer $warehouseTransfer)
    {
        $warehouseTransfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'items.product', 'items.inventoryBatch']);

        return view('warehouse_transfers.finalize', compact('warehouseTransfer'));
    }

    public function finalize(WarehouseTransfer $warehouseTransfer, WarehouseTransferService $service)
    {
        if ($warehouseTransfer->status !== 'draft') {
            return redirect()->route('warehouse-transfers.show', $warehouseTransfer)->with('error', 'Only draft transfers can be finalized.');
        }

        try {
            $service->finalize($warehouseTransfer);
        } catch (RuntimeException $e) {
            return redirect()->route('warehouse-transfers.show', $warehouseTransfer)->with('error', $e->getMessage());
        }

        return redirect()->route('warehouse-transfers.show', $warehouseTransfer)->with('status', 'Warehouse transfer finalized and stock moved.');
    }

    private function saveTransfer(WarehouseTransfer $warehouseTransfer, array $data, WarehouseTransferService $service): WarehouseTransfer
    {
        $isNew = $warehouseTransfer->exists === false;

        $warehouseTransfer->fill([
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id' => $data['to_warehouse_id'],
            'transfer_date' => $data['transfer_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($isNew) {
            $warehouseTransfer->transfer_number = $service->generateTransferNumber();
            $warehouseTransfer->status = 'draft';
            $warehouseTransfer->created_by = auth()->id();
        }

        $warehouseTransfer->save();

        $warehouseTransfer->items()->delete();

        foreach ($data['items'] ?? [] as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            WarehouseTransferItem::create([
                'warehouse_transfer_id' => $warehouseTransfer->id,
                'product_id' => $item['product_id'],
                'inventory_batch_id' => $item['inventory_batch_id'] ?? null,
                'qty' => $qty,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        return $warehouseTransfer;
    }

    private function rules(?WarehouseTransfer $warehouseTransfer = null): array
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.inventory_batch_id' => [
                'nullable',
                'exists:inventory_batches,id',
                function ($attribute, $value, $fail) {
                    $request = request();
                    $fromWarehouseId = $request->input('from_warehouse_id');
                    $productIndex = explode('.', $attribute)[1] ?? null;
                    $productId = $request->input("items.{$productIndex}.product_id");

                    if ($value && $fromWarehouseId && $productId) {
                        $batch = InventoryBatch::where('id', $value)
                            ->where('warehouse_id', $fromWarehouseId)
                            ->where('product_id', $productId)
                            ->where('current_qty', '>', 0)
                            ->first();

                        if ($batch === null) {
                            $fail('Selected batch does not belong to the source warehouse or product.');
                        }
                    }
                },
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
