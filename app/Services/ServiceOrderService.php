<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use Illuminate\Support\Facades\DB;

class ServiceOrderService
{
    public function create(array $data): ServiceOrder
    {
        return DB::transaction(function () use ($data) {
            $serviceOrder = ServiceOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $data['customer_id'],
                'mechanic_id' => $data['mechanic_id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'estimated_completion' => $data['estimated_completion'] ?? null,
                'notes' => $data['notes'] ?? null,
                'labor_cost' => (float) ($data['labor_cost'] ?? 0),
                'other_cost' => (float) ($data['other_cost'] ?? 0),
                'total_amount' => 0,
                'parts_subtotal' => 0,
            ]);

            $this->syncItems($serviceOrder, $data['items'] ?? []);

            return $serviceOrder;
        });
    }

    public function update(ServiceOrder $serviceOrder, array $data): ServiceOrder
    {
        return DB::transaction(function () use ($serviceOrder, $data) {
            $this->releaseStock($serviceOrder);

            $serviceOrder->update([
                'customer_id' => $data['customer_id'] ?? $serviceOrder->customer_id,
                'mechanic_id' => $data['mechanic_id'] ?? null,
                'status' => $data['status'] ?? $serviceOrder->status,
                'estimated_completion' => $data['estimated_completion'] ?? null,
                'notes' => $data['notes'] ?? null,
                'labor_cost' => (float) ($data['labor_cost'] ?? 0),
                'other_cost' => (float) ($data['other_cost'] ?? 0),
            ]);

            $this->syncItems($serviceOrder, $data['items'] ?? []);

            return $serviceOrder;
        });
    }

    public function syncItems(ServiceOrder $serviceOrder, array $items): void
    {
        $serviceOrder->items()->delete();

        $partsSubtotal = 0;

        foreach ($items as $item) {
            $qty = (int) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $itemType = $item['item_type'] ?? 'sparepart';
            $sellingPrice = (float) ($item['selling_price'] ?? 0);
            $buyPrice = (float) ($item['buy_price'] ?? 0);
            $subtotal = $qty * $sellingPrice;
            $inventoryBatchId = null;

            if ($itemType === 'sparepart' && !empty($item['product_id'])) {
                $batches = InventoryBatch::where('product_id', $item['product_id'])
                    ->where('current_qty', '>', 0)
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $totalAvailable = $batches->sum('current_qty');
                if ($totalAvailable < $qty) {
                    $product = Product::find($item['product_id']);
                    throw new \Exception("Stok tidak cukup untuk: {$product->name}. Tersedia: {$totalAvailable}, diminta: {$qty}");
                }

                $remainingQty = $qty;
                $firstBatch = null;
                $buyPrice = 0;

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;
                    $takeQty = min($remainingQty, $batch->current_qty);
                    $remainingQty -= $takeQty;
                    $batch->decrement('current_qty', $takeQty);
                    if (!$firstBatch) {
                        $firstBatch = $batch;
                        $buyPrice = $batch->base_uom_buy_price;
                    }
                }

                $inventoryBatchId = $firstBatch?->id;
                Product::where('id', $item['product_id'])->decrement('total_stock', $qty);
                $partsSubtotal += $subtotal;
            }

            ServiceOrderItem::create([
                'service_order_id' => $serviceOrder->id,
                'product_id' => $item['product_id'] ?? null,
                'item_type' => $itemType,
                'item_name' => $item['item_name'] ?? null,
                'inventory_batch_id' => $inventoryBatchId,
                'qty' => $qty,
                'buy_price' => $buyPrice,
                'selling_price' => $sellingPrice,
                'subtotal' => $subtotal,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $laborCost = (float) $serviceOrder->labor_cost;
        $otherCost = (float) $serviceOrder->other_cost;
        $totalAmount = $partsSubtotal + $laborCost + $otherCost;

        $serviceOrder->update([
            'parts_subtotal' => $partsSubtotal,
            'total_amount' => $totalAmount,
        ]);
    }

    public function releaseStock(ServiceOrder $serviceOrder): void
    {
        $serviceOrder->load('items.product', 'items.batch');

        foreach ($serviceOrder->items as $item) {
            if ($item->item_type === 'sparepart' && $item->inventory_batch_id) {
                if ($item->batch) {
                    $item->batch->increment('current_qty', $item->qty);
                }
                if ($item->product) {
                    $item->product->increment('total_stock', $item->qty);
                }
            }
        }
    }

    public function calculateTotalAmount(array $items): float
    {
        return collect($items)->sum(function (array $item) {
            return ((float) ($item['selling_price'] ?? 0)) * ((int) ($item['qty'] ?? 0));
        });
    }

    public function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $last = ServiceOrder::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->value('id') ?? 0;

        return "WO-{$date}-" . str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
